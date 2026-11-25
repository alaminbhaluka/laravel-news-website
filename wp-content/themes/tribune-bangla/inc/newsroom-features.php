<?php
/**
 * Tribune Bangla newsroom features.
 * These theme-level tools are intentionally self-contained; content remains in WordPress.
 */
if (!defined('ABSPATH')) exit;

/* Newsroom content types: ads, galleries, newsletter and contact inbox. */
add_action('init', function () {
    $types = [
        'tb_ad' => ['বিজ্ঞাপন', 'বিজ্ঞাপন', 'বিজ্ঞাপনসমূহ'],
        'tb_gallery' => ['ফটো গ্যালারি', 'গ্যালারি', 'গ্যালারিসমূহ'],
        'tb_subscriber' => ['নিউজলেটার গ্রাহক', 'গ্রাহক', 'গ্রাহক তালিকা'],
        'tb_contact' => ['পাঠকের বার্তা', 'বার্তা', 'পাঠকের বার্তা'],
    ];
    foreach ($types as $type => $labels) {
        register_post_type($type, [
            'labels' => ['name'=>$labels[0], 'singular_name'=>$labels[1], 'menu_name'=>$labels[2], 'add_new_item'=>$labels[1].' যোগ করুন'],
            'public' => in_array($type, ['tb_gallery'], true),
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => in_array($type, ['tb_gallery'], true),
            'supports' => $type === 'tb_gallery' ? ['title','editor','thumbnail','excerpt'] : ['title'],
            'has_archive' => $type === 'tb_gallery',
            'rewrite' => $type === 'tb_gallery' ? ['slug'=>'gallery'] : false,
            'menu_icon' => $type === 'tb_ad' ? 'dashicons-megaphone' : ($type === 'tb_gallery' ? 'dashicons-format-gallery' : 'dashicons-email'),
        ]);
    }
}, 5);

/* Reporter role: submit and manage only their own unpublished stories. */
add_action('init', function () {
    if (!get_role('tb_reporter')) {
        add_role('tb_reporter', 'প্রতিবেদক', [
            'read'=>true, 'edit_posts'=>true, 'edit_published_posts'=>false,
            'publish_posts'=>false, 'delete_posts'=>false, 'upload_files'=>true,
        ]);
    }
});

/* Ad inventory fields and safe rendering. */
add_action('add_meta_boxes', function () {
    add_meta_box('tb_ad_details', 'বিজ্ঞাপনের তথ্য', function ($post) {
        wp_nonce_field('tb_save_ad', 'tb_ad_nonce');
        $fields = ['image'=>'ছবির URL','target'=>'বিজ্ঞাপনের লিংক','placement'=>'অবস্থান (header/sidebar/footer)','active'=>'সক্রিয় (1/0)'];
        foreach ($fields as $key=>$label) {
            $value = get_post_meta($post->ID, '_tb_ad_'.$key, true);
            echo '<p><label>'.esc_html($label).'<br><input style="width:100%" name="tb_ad_'.$key.'" value="'.esc_attr($value).'"></label></p>';
        }
    }, 'tb_ad', 'normal', 'high');
    foreach (['post','page'] as $screen) {
        add_meta_box('tb_seo_details', 'SEO ও সামাজিক শেয়ার সেটিংস', 'tribune_bangla_seo_box', $screen, 'normal', 'default');
    }
});
function tribune_bangla_seo_box($post) {
    wp_nonce_field('tb_save_seo', 'tb_seo_nonce');
    foreach (['title'=>'SEO শিরোনাম','description'=>'Meta description'] as $key=>$label) {
        echo '<p><label>'.esc_html($label).'<br><input style="width:100%" name="tb_seo_'.$key.'" value="'.esc_attr(get_post_meta($post->ID, '_tb_seo_'.$key, true)).'"></label></p>';
    }
}
add_action('save_post', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (isset($_POST['tb_ad_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tb_ad_nonce'])), 'tb_save_ad') && current_user_can('edit_post', $post_id)) {
        foreach (['image','target','placement','active'] as $key) {
            if (isset($_POST['tb_ad_'.$key])) {
                $raw = wp_unslash($_POST['tb_ad_'.$key]);
                $value = $key === 'image' || $key === 'target' ? esc_url_raw($raw) : sanitize_text_field($raw);
                update_post_meta($post_id, '_tb_ad_'.$key, $value);
            }
        }
    }
    if (isset($_POST['tb_seo_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tb_seo_nonce'])), 'tb_save_seo') && current_user_can('edit_post', $post_id)) {
        foreach (['title','description'] as $key) {
            if (isset($_POST['tb_seo_'.$key])) update_post_meta($post_id, '_tb_seo_'.$key, sanitize_text_field(wp_unslash($_POST['tb_seo_'.$key])));
        }
    }
});

/* Shortcode: [tribune_ads position="sidebar"] */
add_shortcode('tribune_ads', function ($atts) {
    $atts = shortcode_atts(['position'=>'sidebar','limit'=>3], $atts, 'tribune_ads');
    $q = new WP_Query(['post_type'=>'tb_ad','posts_per_page'=>max(1,min(10,(int)$atts['limit'])),'post_status'=>'publish','meta_query'=>[
        ['key'=>'_tb_ad_active','value'=>'1'], ['key'=>'_tb_ad_placement','value'=>sanitize_key($atts['position'])]
    ]]);
    if (!$q->have_posts()) return '';
    ob_start(); echo '<div class="tb-advertisements tb-ad-'.esc_attr(sanitize_html_class($atts['position'])).'">';
    while ($q->have_posts()) { $q->the_post(); $img=get_post_meta(get_the_ID(),'_tb_ad_image',true); $url=get_post_meta(get_the_ID(),'_tb_ad_target',true); if ($img) {
        echo '<div class="tb-ad-item"><a href="'.esc_url($url ?: '#').'" target="_blank" rel="sponsored noopener"><img src="'.esc_url($img).'" alt="'.esc_attr(get_the_title()).'"></a><div class="tb-ad-copy">'.esc_html(get_the_title()).'</div></div>';
    }}
    echo '</div>'; wp_reset_postdata(); return ob_get_clean();
});

/* Newsletter: stores subscriber addresses privately in WordPress admin. */
add_shortcode('tribune_newsletter', function () {
    $message = '';
    if (isset($_POST['tb_newsletter_submit'])) {
        if (!isset($_POST['tb_newsletter_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tb_newsletter_nonce'])), 'tb_newsletter')) {
            $message = 'অনুরোধটি যাচাই করা যায়নি। আবার চেষ্টা করুন।';
        } else {
            $email = isset($_POST['tb_newsletter_email']) ? sanitize_email(wp_unslash($_POST['tb_newsletter_email'])) : '';
            if (!is_email($email)) $message = 'সঠিক ইমেইল ঠিকানা লিখুন।';
            else {
                $exists = get_posts(['post_type'=>'tb_subscriber','post_status'=>'private','numberposts'=>1,'meta_key'=>'_tb_subscriber_email','meta_value'=>$email]);
                if (!$exists) {
                    $id = wp_insert_post(['post_type'=>'tb_subscriber','post_status'=>'private','post_title'=>$email]);
                    if (!is_wp_error($id)) { update_post_meta($id, '_tb_subscriber_email', $email); do_action('save_post_tb_subscriber', $id, get_post($id), true); }
                }
                $message = 'নিউজলেটার তালিকায় আপনার ইমেইল যুক্ত হয়েছে।';
            }
        }
    }
    ob_start(); echo '<form class="tb-newsletter-form" method="post"><label class="screen-reader-text" for="tb-newsletter-email">ইমেইল</label><input id="tb-newsletter-email" type="email" name="tb_newsletter_email" placeholder="আপনার ইমেইল ঠিকানা" required>'; wp_nonce_field('tb_newsletter','tb_newsletter_nonce'); echo '<button type="submit" name="tb_newsletter_submit" value="1">সাবস্ক্রাইব</button></form>';
    if ($message) echo '<p role="status">'.esc_html($message).'</p>'; return ob_get_clean();
});

/* Contact form: messages are stored privately for administrators. */
add_shortcode('tribune_contact_form', function () {
    $message = '';
    if (isset($_POST['tb_contact_submit'])) {
        if (!isset($_POST['tb_contact_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tb_contact_nonce'])), 'tb_contact')) $message='অনুরোধটি যাচাই করা যায়নি।';
        else {
            $name=sanitize_text_field(wp_unslash($_POST['tb_contact_name'] ?? ''));
            $email=sanitize_email(wp_unslash($_POST['tb_contact_email'] ?? ''));
            $body=sanitize_textarea_field(wp_unslash($_POST['tb_contact_message'] ?? ''));
            if (!$name || !is_email($email) || !$body) $message='সব তথ্য সঠিকভাবে পূরণ করুন।';
            else {
                $id=wp_insert_post(['post_type'=>'tb_contact','post_status'=>'private','post_title'=>'বার্তা: '.$name]);
                if (!is_wp_error($id)) { update_post_meta($id,'_tb_contact_email',$email); update_post_meta($id,'_tb_contact_body',$body); $message='আপনার বার্তা গ্রহণ করা হয়েছে।'; }
            }
        }
    }
    ob_start(); echo '<form class="tb-contact-form" method="post"><p><label>নাম<input name="tb_contact_name" required></label></p><p><label>ইমেইল<input type="email" name="tb_contact_email" required></label></p><p><label>বার্তা<textarea name="tb_contact_message" rows="5" required></textarea></label></p>'; wp_nonce_field('tb_contact','tb_contact_nonce'); echo '<button name="tb_contact_submit" value="1">বার্তা পাঠান</button></form>';
    if ($message) echo '<p role="status">'.esc_html($message).'</p>'; return ob_get_clean();
});

/* Front-end reporter desk. Stories are always submitted for editorial review. */
add_shortcode('tribune_reporter_panel', function () {
    if (!is_user_logged_in() || !current_user_can('edit_posts')) return '<p>এই প্যানেল ব্যবহার করতে প্রতিবেদক হিসেবে লগইন করুন।</p>';
    $user=wp_get_current_user(); $notice='';
    if (isset($_POST['tb_reporter_submit'])) {
        if (!isset($_POST['tb_reporter_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tb_reporter_nonce'])), 'tb_reporter_submit')) $notice='নিরাপত্তা যাচাই ব্যর্থ হয়েছে।';
        else {
            $title=sanitize_text_field(wp_unslash($_POST['tb_story_title'] ?? ''));
            $content=wp_kses_post(wp_unslash($_POST['tb_story_content'] ?? ''));
            $cat=absint($_POST['tb_story_category'] ?? 0);
            if (!$title || !$content) $notice='শিরোনাম ও সংবাদ লিখুন।';
            else {
                $post_id=wp_insert_post(['post_type'=>'post','post_status'=>'pending','post_title'=>$title,'post_content'=>$content,'post_author'=>$user->ID], true);
                if (!is_wp_error($post_id)) {
                    if ($cat && term_exists($cat,'category')) wp_set_post_categories($post_id, [$cat]);
                    if (!empty($_FILES['tb_story_image']['name'])) {
                        require_once ABSPATH.'wp-admin/includes/file.php'; require_once ABSPATH.'wp-admin/includes/media.php'; require_once ABSPATH.'wp-admin/includes/image.php';
                        $attachment=media_handle_upload('tb_story_image',$post_id);
                        if (!is_wp_error($attachment)) set_post_thumbnail($post_id,$attachment);
                    }
                    $notice='সংবাদটি সম্পাদকের অনুমোদনের জন্য জমা হয়েছে।';
                } else $notice='সংবাদ জমা দেওয়া যায়নি। আবার চেষ্টা করুন।';
            }
        }
    }
    $mine=get_posts(['post_type'=>'post','author'=>$user->ID,'post_status'=>['pending','draft','publish','future'],'numberposts'=>10]);
    ob_start(); echo '<section class="tb-reporter-panel"><h2>প্রতিবেদক প্যানেল</h2>';
    if ($notice) echo '<p role="status">'.esc_html($notice).'</p>';
    echo '<form class="tb-contact-form" method="post" enctype="multipart/form-data"><p><label>সংবাদের শিরোনাম<input name="tb_story_title" required></label></p><p><label>সংবাদ বিবরণ<textarea name="tb_story_content" rows="9" required></textarea></label></p><p><label>ক্যাটাগরি<select name="tb_story_category"><option value="0">নির্বাচন করুন</option>';
    foreach (get_categories(['hide_empty'=>false]) as $category) echo '<option value="'.(int)$category->term_id.'">'.esc_html($category->name).'</option>';
    echo '</select></label></p><p><label>প্রধান ছবি<input type="file" name="tb_story_image" accept="image/jpeg,image/png,image/webp"></label></p>'; wp_nonce_field('tb_reporter_submit','tb_reporter_nonce'); echo '<button name="tb_reporter_submit" value="1">সম্পাদকের কাছে জমা দিন</button></form><h3>আপনার সাম্প্রতিক সংবাদ</h3><ul class="tb-list">';
    foreach ($mine as $story) echo '<li>'.esc_html($story->post_title).' — '.esc_html(get_post_status_object($story->post_status)->label).'</li>';
    echo '</ul></section>'; return ob_get_clean();
});

/* Mobile number login/registration. Registration is disabled unless site settings permit it. */
add_shortcode('tribune_mobile_account', function () {
    $notice='';
    if (is_user_logged_in()) return '<p>আপনি লগইন করেছেন। <a href="'.esc_url(wp_logout_url(get_permalink())).'">লগআউট</a></p>';
    if (isset($_POST['tb_mobile_register'])) {
        if (!isset($_POST['tb_mobile_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tb_mobile_nonce'])), 'tb_mobile_account')) $notice='নিরাপত্তা যাচাই ব্যর্থ হয়েছে।';
        elseif (!get_option('users_can_register')) $notice='এই সাইটে নতুন নিবন্ধন বন্ধ আছে।';
        else {
            $mobile=preg_replace('/\D+/', '', wp_unslash($_POST['tb_mobile'] ?? ''));
            $password=(string)($_POST['tb_mobile_password'] ?? '');
            if (!preg_match('/^01[3-9][0-9]{8}$/',$mobile) || strlen($password)<10) $notice='সঠিক মোবাইল নম্বর ও কমপক্ষে ১০ অক্ষরের পাসওয়ার্ড দিন।';
            elseif (username_exists($mobile)) $notice='এই মোবাইল নম্বরে অ্যাকাউন্ট রয়েছে।';
            else {
                $uid=wp_create_user($mobile,$password);
                if (!is_wp_error($uid)) { update_user_meta($uid,'tb_mobile_number',$mobile); $notice='নিবন্ধন সফল। এখন লগইন করুন।'; }
                else $notice='নিবন্ধন করা যায়নি।';
            }
        }
    }
    if (isset($_POST['tb_mobile_login'])) {
        if (!isset($_POST['tb_mobile_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tb_mobile_nonce'])), 'tb_mobile_account')) $notice='নিরাপত্তা যাচাই ব্যর্থ হয়েছে।';
        else {
            $mobile=preg_replace('/\D+/', '', wp_unslash($_POST['tb_mobile_login_number'] ?? ''));
            $user=wp_signon(['user_login'=>$mobile,'user_password'=>(string)($_POST['tb_mobile_login_password'] ?? ''),'remember'=>true], is_ssl());
            if (!is_wp_error($user)) { wp_safe_redirect(get_permalink()); exit; } $notice='মোবাইল নম্বর অথবা পাসওয়ার্ড সঠিক নয়।';
        }
    }
    ob_start(); echo '<div class="tb-account-forms"><form method="post"><h2>মোবাইল নম্বরে লগইন</h2><input name="tb_mobile_login_number" inputmode="numeric" placeholder="মোবাইল নম্বর" required><input type="password" name="tb_mobile_login_password" placeholder="পাসওয়ার্ড" required>'; wp_nonce_field('tb_mobile_account','tb_mobile_nonce'); echo '<button name="tb_mobile_login" value="1">লগইন</button></form><form method="post"><h2>নতুন নিবন্ধন</h2><input name="tb_mobile" inputmode="numeric" placeholder="মোবাইল নম্বর" required><input type="password" name="tb_mobile_password" minlength="10" placeholder="কমপক্ষে ১০ অক্ষরের পাসওয়ার্ড" required>'; wp_nonce_field('tb_mobile_account','tb_mobile_nonce'); echo '<button name="tb_mobile_register" value="1">নিবন্ধন</button></form></div>';
    if ($notice) echo '<p role="status">'.esc_html($notice).'</p>'; return ob_get_clean();
});

/* Basic metadata and JSON-LD; avoid duplicating SEO plugin output. */
add_action('wp_head', function () {
    if (defined('WPSEO_VERSION') || class_exists('RankMath') || defined('AIOSEO_VERSION')) return;
    if (is_singular()) {
        $id=get_queried_object_id();
        $title=get_post_meta($id,'_tb_seo_title',true) ?: wp_get_document_title();
        $description=get_post_meta($id,'_tb_seo_description',true);
        if (!$description) $description=wp_strip_all_tags(get_the_excerpt($id));
        if ($description) echo '<meta name="description" content="'.esc_attr(wp_trim_words($description,30,'')).'">'."\n";
        echo '<meta property="og:title" content="'.esc_attr($title).'">'."\n";
        if (has_post_thumbnail($id)) echo '<meta property="og:image" content="'.esc_url(get_the_post_thumbnail_url($id,'large')).'">'."\n";
        echo '<meta property="og:type" content="article">'."\n";
    }
}, 5);

/* Gallery shortcode: [tribune_gallery limit="12"] */
add_shortcode('tribune_gallery', function ($atts) {
    $atts=shortcode_atts(['limit'=>12],$atts,'tribune_gallery');
    $q=new WP_Query(['post_type'=>'tb_gallery','post_status'=>'publish','posts_per_page'=>max(1,min(30,(int)$atts['limit']))]);
    if (!$q->have_posts()) return '<p>এখনো কোনো গ্যালারি প্রকাশিত হয়নি।</p>';
    ob_start(); echo '<div class="tb-gallery-grid">';
    while ($q->have_posts()) { $q->the_post(); echo '<article class="tb-gallery-card"><a href="'.esc_url(get_permalink()).'">'; if (has_post_thumbnail()) the_post_thumbnail('medium_large'); echo '<h3>'.esc_html(get_the_title()).'</h3></a></article>'; }
    echo '</div>'; wp_reset_postdata(); return ob_get_clean();
});
