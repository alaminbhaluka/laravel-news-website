<?php
/**
 * Read-only by default Laravel JSON importer for WP-CLI.
 * Usage: wp tribune import /secure/path/export.json
 * Apply only after staging backup/review: wp tribune import /secure/path/export.json --apply
 */
if (!defined('WP_CLI') || !WP_CLI) return;

class TB_Legacy_Importer {
    private $source = 'laravel';
    private $stats = ['categories'=>0,'tags'=>0,'posts'=>0,'pages'=>0,'comments'=>0,'skipped'=>0,'errors'=>0];
    private $term_map = ['categories'=>[],'tags'=>[]];

    /**
     * Import a JSON export. Without --apply this command is a dry-run.
     *
     * ## OPTIONS
     * <file>
     * : Absolute path to UTF-8 JSON export.
     *
     * [--apply]
     * : Actually create records. Default is dry-run.
     *
     * [--source=<name>]
     * : Legacy source label. Default laravel.
     *
     * [--author=<id>]
     * : WordPress user ID used when no author mapping is provided.
     *
     * ## EXAMPLE
     *     wp tribune import /secure/laravel-export.json
     *     wp tribune import /secure/laravel-export.json --apply --author=1
     */
    public function import($args, $assoc_args) {
        $file = $args[0] ?? '';
        $apply = isset($assoc_args['apply']);
        $this->source = sanitize_key($assoc_args['source'] ?? 'laravel');
        if (!$file || !is_readable($file)) WP_CLI::error('Export JSON file পাওয়া যায়নি বা পড়া যাচ্ছে না।');
        $raw = file_get_contents($file);
        if ($raw === false || strlen($raw) > 100 * MB_IN_BYTES) WP_CLI::error('ফাইল পড়া যায়নি অথবা ১০০ MB সীমা অতিক্রম করেছে।');
        $data = json_decode($raw, true);
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) WP_CLI::error('সঠিক UTF-8 JSON export দিন।');
        foreach (['categories','tags','posts','pages','comments'] as $key) {
            if (isset($data[$key]) && !is_array($data[$key])) WP_CLI::error($key.' অবশ্যই JSON array হতে হবে।');
        }

        WP_CLI::log($apply ? 'IMPORT MODE: WordPress-এ নতুন রেকর্ড তৈরি হবে।' : 'DRY-RUN: কোনো তথ্য লেখা হবে না।');
        WP_CLI::log('Source: '.$this->source);
        foreach (['categories','tags','posts','pages','comments'] as $key) {
            WP_CLI::log(sprintf('%s: %d', $key, isset($data[$key]) ? count($data[$key]) : 0));
        }
        if (!$apply) {
            WP_CLI::success('Dry-run সম্পন্ন। প্রকৃত import-এর আগে export ও mapping যাচাই করুন; প্রয়োজনে --apply দিন।');
            return;
        }

        if (!current_user_can('import')) WP_CLI::error('বর্তমান WP-CLI user-এর import capability নেই।');
        $this->import_terms($data['categories'] ?? [], 'category');
        $this->import_terms($data['tags'] ?? [], 'post_tag');
        $this->import_posts($data['posts'] ?? [], 'post', (int)($assoc_args['author'] ?? 0));
        $this->import_posts($data['pages'] ?? [], 'page', (int)($assoc_args['author'] ?? 0));
        $this->import_comments($data['comments'] ?? []);
        WP_CLI::log('ফলাফল: '.wp_json_encode($this->stats, JSON_UNESCAPED_UNICODE));
        WP_CLI::success('Import ধাপ শেষ। record count, author, media ও permalink হাতে-কলমে যাচাই করুন।');
    }

    private function legacy_id($row) {
        return isset($row['id']) ? (string)$row['id'] : '';
    }

    private function existing_id($type, $legacy_id) {
        if ($legacy_id === '') return 0;
        $ids = get_posts([
            'post_type'=>$type, 'post_status'=>'any', 'fields'=>'ids',
            'posts_per_page'=>1, 'meta_key'=>'_tb_legacy_id',
            'meta_value'=>$legacy_id, 'suppress_filters'=>true
        ]);
        return $ids ? (int)$ids[0] : 0;
    }

    private function import_terms($rows, $taxonomy) {
        foreach ($rows as $row) {
            if (!is_array($row)) { $this->stats['errors']++; continue; }
            $legacy = $this->legacy_id($row);
            $name = sanitize_text_field($row['name'] ?? $row['title'] ?? '');
            if (!$name) { $this->stats['errors']++; continue; }
            $slug = sanitize_title($row['slug'] ?? $name);
            $term = term_exists($slug, $taxonomy);
            if (!$term) $term = term_exists($name, $taxonomy);
            if (!$term) {
                $term = wp_insert_term($name, $taxonomy, ['slug'=>$slug]);
                if (is_wp_error($term)) { $this->stats['errors']++; WP_CLI::warning($term->get_error_message()); continue; }
                $term_id = (int)$term['term_id'];
            } else {
                $term_id = (int)(is_array($term) ? $term['term_id'] : $term);
            }
            if ($legacy !== '') update_term_meta($term_id, '_tb_legacy_'.$this->source.'_id', $legacy);
            $this->term_map[$taxonomy==='category'?'categories':'tags'][$legacy] = $term_id;
            $this->stats[$taxonomy==='category'?'categories':'tags']++;
        }
    }

    private function author_id($row, $fallback) {
        $legacy_author = isset($row['author_id']) ? (string)$row['author_id'] : '';
        if ($legacy_author !== '') {
            $users = get_users(['meta_key'=>'_tb_legacy_'.$this->source.'_user_id','meta_value'=>$legacy_author,'number'=>1,'fields'=>'ID']);
            if ($users) return (int)$users[0];
        }
        return $fallback > 0 && get_user_by('id',$fallback) ? $fallback : 0;
    }

    private function import_posts($rows, $type, $fallback_author) {
        foreach ($rows as $row) {
            if (!is_array($row)) { $this->stats['errors']++; continue; }
            $legacy = $this->legacy_id($row);
            $title = sanitize_text_field($row['title'] ?? $row['headline'] ?? '');
            if (!$title) { $this->stats['errors']++; continue; }
            if ($legacy !== '' && $this->existing_id($type,$legacy)) { $this->stats['skipped']++; continue; }
            $status = sanitize_key($row['status'] ?? 'draft');
            $allowed = ['publish','draft','pending','private','future'];
            if (!in_array($status,$allowed,true)) $status='draft';
            $date = isset($row['date']) ? sanitize_text_field($row['date']) : '';
            $content = (string)($row['content'] ?? $row['details'] ?? '');
            $record = [
                'post_type'=>$type, 'post_status'=>$status, 'post_title'=>$title,
                'post_content'=>wp_kses_post($content),
                'post_excerpt'=>sanitize_textarea_field($row['excerpt'] ?? $row['quote'] ?? ''),
                'post_name'=>sanitize_title($row['slug'] ?? $title),
                'post_author'=>$this->author_id($row,$fallback_author),
            ];
            if ($date && strtotime($date)) $record['post_date']=gmdate('Y-m-d H:i:s',strtotime($date));
            $id = wp_insert_post(wp_slash($record), true);
            if (is_wp_error($id)) { $this->stats['errors']++; WP_CLI::warning($id->get_error_message()); continue; }
            if ($legacy !== '') update_post_meta($id,'_tb_legacy_'.$this->source.'_id',$legacy);
            if (isset($row['category_ids']) && is_array($row['category_ids']) && $type==='post') {
                $ids=[];
                foreach ($row['category_ids'] as $old_id) if (isset($this->term_map['categories'][(string)$old_id])) $ids[]=$this->term_map['categories'][(string)$old_id];
                if ($ids) wp_set_post_categories($id,$ids,false);
            }
            if (isset($row['tag_ids']) && is_array($row['tag_ids']) && $type==='post') {
                $ids=[];
                foreach ($row['tag_ids'] as $old_id) if (isset($this->term_map['tags'][(string)$old_id])) $ids[]=$this->term_map['tags'][(string)$old_id];
                if ($ids) wp_set_post_terms($id,$ids,'post_tag',false);
            }
            foreach (['language','translation_group','legacy_url'] as $field) {
                if (isset($row[$field])) update_post_meta($id,'_tb_legacy_'.$field,sanitize_text_field($row[$field]));
            }
            $this->stats[$type==='post'?'posts':'pages']++;
        }
    }

    private function import_comments($rows) {
        foreach ($rows as $row) {
            if (!is_array($row) || empty($row['post_id'])) { $this->stats['errors']++; continue; }
            $legacy_post=(string)$row['post_id'];
            $posts=get_posts(['post_type'=>['post','page'],'post_status'=>'any','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_tb_legacy_'.$this->source.'_id','meta_value'=>$legacy_post]);
            if (!$posts) { $this->stats['skipped']++; continue; }
            $legacy_comment=$this->legacy_id($row);
            if ($legacy_comment !== '') {
                $found=get_comments(['meta_key'=>'_tb_legacy_'.$this->source.'_comment_id','meta_value'=>$legacy_comment,'number'=>1,'count'=>true]);
                if ($found) { $this->stats['skipped']++; continue; }
            }
            $comment_id=wp_insert_comment([
                'comment_post_ID'=>(int)$posts[0],
                'comment_author'=>sanitize_text_field($row['author'] ?? 'পাঠক'),
                'comment_author_email'=>sanitize_email($row['email'] ?? ''),
                'comment_content'=>sanitize_textarea_field($row['content'] ?? ''),
                'comment_date'=>!empty($row['date']) && strtotime($row['date']) ? gmdate('Y-m-d H:i:s',strtotime($row['date'])) : current_time('mysql'),
                'comment_approved'=>!empty($row['approved']) ? 1 : 0,
            ]);
            if ($comment_id && $legacy_comment !== '') add_comment_meta($comment_id,'_tb_legacy_'.$this->source.'_comment_id',$legacy_comment,true);
            if ($comment_id) $this->stats['comments']++; else $this->stats['errors']++;
        }
    }
}
WP_CLI::add_command('tribune import', 'TB_Legacy_Importer');
