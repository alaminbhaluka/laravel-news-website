<?php
/**
 * Tribune Bangla: newsletter delivery, ad analytics and news sitemaps.
 */
if (!defined('ABSPATH')) exit;

/* ---------- Newsletter delivery and unsubscribe ---------- */
function tb_newsletter_token($post_id) {
    $token = get_post_meta($post_id, '_tb_subscriber_token', true);
    if (!$token) {
        $token = wp_generate_password(32, false, false);
        update_post_meta($post_id, '_tb_subscriber_token', $token);
    }
    return $token;
}
add_action('save_post_tb_subscriber', function ($post_id) {
    if (wp_is_post_revision($post_id)) return;
    if (!get_post_meta($post_id, '_tb_subscriber_status', true)) update_post_meta($post_id, '_tb_subscriber_status', 'active');
    tb_newsletter_token($post_id);
}, 20);

/* Add a subscriber to the list from the existing form, using explicit active consent. */
add_filter('tb_newsletter_subscriber_created', function ($post_id) {
    update_post_meta($post_id, '_tb_subscriber_status', 'active');
    tb_newsletter_token($post_id);
    return $post_id;
});

/* Send newly published articles to opted-in subscribers. wp_mail uses the host mailer;
 * configure an authenticated SMTP transport (e.g. a reputable SMTP plugin/provider) for delivery.
 */
add_action('transition_post_status', function ($new_status, $old_status, $post) {
    if ($new_status !== 'publish' || $old_status === 'publish' || $post->post_type !== 'post' || wp_is_post_revision($post->ID)) return;
    if (get_post_meta($post->ID, '_tb_newsletter_sent', true)) return;
    $subscribers = get_posts([
        'post_type'=>'tb_subscriber','post_status'=>'private','numberposts'=>-1,
        'meta_query'=>[['key'=>'_tb_subscriber_email','compare'=>'EXISTS']]
    ]);
    if (!$subscribers) return;
    $subject = '['.wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES).'] '.get_the_title($post);
    $excerpt = wp_strip_all_tags(get_the_excerpt($post));
    if (!$excerpt) $excerpt = wp_trim_words(wp_strip_all_tags($post->post_content), 45);
    $sent = 0;
    foreach ($subscribers as $subscriber) {
        $status = get_post_meta($subscriber->ID, '_tb_subscriber_status', true);
        if ($status === 'unsubscribed') continue;
        $email = sanitize_email(get_post_meta($subscriber->ID, '_tb_subscriber_email', true));
        if (!is_email($email)) continue;
        $unsubscribe = add_query_arg([
            'action'=>'tb_newsletter_unsubscribe',
            'subscriber'=>$subscriber->ID,
            'token'=>tb_newsletter_token($subscriber->ID)
        ], admin_url('admin-post.php'));
        $body = "নতুন সংবাদ প্রকাশিত হয়েছে\n\n".get_the_title($post)."\n\n".$excerpt."\n\nসম্পূর্ণ সংবাদ পড়ুন: ".get_permalink($post)."\n\nনিউজলেটার বন্ধ করতে: ".$unsubscribe;
        if (wp_mail($email, $subject, $body, ['Content-Type: text/plain; charset=UTF-8'])) $sent++;
    }
    update_post_meta($post->ID, '_tb_newsletter_sent', current_time('mysql'));
    update_post_meta($post->ID, '_tb_newsletter_sent_count', $sent);
}, 20, 3);

add_action('admin_post_nopriv_tb_newsletter_unsubscribe', 'tb_newsletter_unsubscribe');
add_action('admin_post_tb_newsletter_unsubscribe', 'tb_newsletter_unsubscribe');
function tb_newsletter_unsubscribe() {
    $id = absint($_GET['subscriber'] ?? 0);
    $token = sanitize_text_field(wp_unslash($_GET['token'] ?? ''));
    $saved = $id ? get_post_meta($id, '_tb_subscriber_token', true) : '';
    if (!$id || !$saved || !hash_equals($saved, $token)) wp_die('লিংকটি সঠিক নয়।', 'ত্রুটি', ['response'=>403]);
    update_post_meta($id, '_tb_subscriber_status', 'unsubscribed');
    wp_die('আপনাকে নিউজলেটার তালিকা থেকে বাদ দেওয়া হয়েছে।', 'নিউজলেটার');
}

/* ---------- Ad impressions, click-through tracking and revenue report ---------- */
add_action('add_meta_boxes', function () {
    add_meta_box('tb_ad_reporting', 'বিজ্ঞাপনের রিপোর্ট ও আয়', function ($post) {
        wp_nonce_field('tb_save_ad_report', 'tb_ad_report_nonce');
        $views = (int)get_post_meta($post->ID, '_tb_ad_impressions', true);
        $clicks = (int)get_post_meta($post->ID, '_tb_ad_clicks', true);
        $revenue = get_post_meta($post->ID, '_tb_ad_revenue', true);
        echo '<p>প্রদর্শন: <strong>'.number_format_i18n($views).'</strong> | ক্লিক: <strong>'.number_format_i18n($clicks).'</strong></p>';
        echo '<p><label>চুক্তি অনুযায়ী আয় (টাকা)<br><input type="number" min="0" step="0.01" name="tb_ad_revenue" value="'.esc_attr($revenue).'" style="width:100%"></label></p>';
        echo '<p class="description">আয়ের অঙ্কটি ম্যানুয়ালি লিখুন; এটি স্বয়ংক্রিয় বিজ্ঞাপন নেটওয়ার্ক আয় নয়।</p>';
    }, 'tb_ad', 'normal', 'default');
});
add_action('save_post_tb_ad', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['tb_ad_report_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tb_ad_report_nonce'])), 'tb_save_ad_report')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (isset($_POST['tb_ad_revenue'])) update_post_meta($post_id, '_tb_ad_revenue', max(0, (float)wp_unslash($_POST['tb_ad_revenue'])));
});

add_action('admin_post_tb_ad_click', 'tb_ad_click_redirect');
add_action('admin_post_nopriv_tb_ad_click', 'tb_ad_click_redirect');
function tb_ad_click_redirect() {
    $id = absint($_GET['id'] ?? 0);
    $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? ''));
    if (!$id || !wp_verify_nonce($nonce, 'tb_ad_click_'.$id)) wp_die('বিজ্ঞাপনের লিংকটি সঠিক নয়।', 'ত্রুটি', ['response'=>403]);
    $target = get_post_meta($id, '_tb_ad_target', true);
    if (get_post_type($id) !== 'tb_ad' || get_post_status($id) !== 'publish' || !wp_http_validate_url($target)) wp_safe_redirect(home_url('/'));
    else {
        $clicks = (int)get_post_meta($id, '_tb_ad_clicks', true);
        update_post_meta($id, '_tb_ad_clicks', $clicks + 1);
        wp_redirect(esc_url_raw($target), 302);
    }
    exit;
}
add_shortcode('tribune_ads', function ($atts) {
    $atts = shortcode_atts(['position'=>'sidebar','limit'=>3], $atts, 'tribune_ads');
    $q = new WP_Query(['post_type'=>'tb_ad','posts_per_page'=>max(1,min(10,(int)$atts['limit'])),'post_status'=>'publish','meta_query'=>[
        ['key'=>'_tb_ad_active','value'=>'1'], ['key'=>'_tb_ad_placement','value'=>sanitize_key($atts['position'])]
    ]]);
    if (!$q->have_posts()) return '';
    ob_start();
    echo '<div class="tb-advertisements tb-ad-'.esc_attr(sanitize_html_class($atts['position'])).'">';
    while ($q->have_posts()) {
        $q->the_post();
        $id=get_the_ID(); $img=get_post_meta($id,'_tb_ad_image',true);
        $target=admin_url('admin-post.php?action=tb_ad_click&id='.$id);
        $target=wp_nonce_url($target,'tb_ad_click_'.$id);
        $views=(int)get_post_meta($id,'_tb_ad_impressions',true);
        update_post_meta($id,'_tb_ad_impressions',$views+1);
        if ($img) echo '<div class="tb-ad-item"><a href="'.esc_url($target).'" target="_blank" rel="sponsored noopener"><img src="'.esc_url($img).'" alt="'.esc_attr(get_the_title()).'"></a><div class="tb-ad-copy">'.esc_html(get_the_title()).'</div></div>';
    }
    echo '</div>'; wp_reset_postdata(); return ob_get_clean();
});

/* Admin report page for campaign totals. */
add_action('admin_menu', function () {
    add_submenu_page('edit.php?post_type=tb_ad','বিজ্ঞাপন রিপোর্ট','বিজ্ঞাপন রিপোর্ট','manage_options','tb-ad-report','tb_render_ad_report');
});
function tb_render_ad_report() {
    if (!current_user_can('manage_options')) return;
    $ads=get_posts(['post_type'=>'tb_ad','post_status'=>['publish','draft','private'],'numberposts'=>-1]);
    $views=0; $clicks=0; $revenue=0;
    foreach ($ads as $ad) { $views+=(int)get_post_meta($ad->ID,'_tb_ad_impressions',true); $clicks+=(int)get_post_meta($ad->ID,'_tb_ad_clicks',true); $revenue+=(float)get_post_meta($ad->ID,'_tb_ad_revenue',true); }
    echo '<div class="wrap"><h1>বিজ্ঞাপন রিপোর্ট</h1><p>মোট প্রদর্শন: <strong>'.number_format_i18n($views).'</strong> | মোট ক্লিক: <strong>'.number_format_i18n($clicks).'</strong> | নথিভুক্ত আয়: <strong>'.number_format_i18n($revenue,2).' টাকা</strong></p>';
    echo '<table class="widefat striped"><thead><tr><th>বিজ্ঞাপন</th><th>প্রদর্শন</th><th>ক্লিক</th><th>CTR</th><th>আয় (টাকা)</th></tr></thead><tbody>';
    foreach ($ads as $ad) {
        $v=(int)get_post_meta($ad->ID,'_tb_ad_impressions',true); $c=(int)get_post_meta($ad->ID,'_tb_ad_clicks',true); $r=(float)get_post_meta($ad->ID,'_tb_ad_revenue',true);
        $ctr=$v ? number_format_i18n(($c/$v)*100,2).'%' : '—';
        echo '<tr><td><a href="'.esc_url(get_edit_post_link($ad->ID)).'">'.esc_html($ad->post_title).'</a></td><td>'.number_format_i18n($v).'</td><td>'.number_format_i18n($c).'</td><td>'.$ctr.'</td><td>'.number_format_i18n($r,2).'</td></tr>';
    }
    if (!$ads) echo '<tr><td colspan="5">এখনো কোনো বিজ্ঞাপন যোগ করা হয়নি।</td></tr>';
    echo '</tbody></table><p class="description">প্রদর্শন সংখ্যা পেজে বিজ্ঞাপন রেন্ডার হওয়ার ভিত্তিতে; এটি viewability-verified impression নয়। আয় ম্যানুয়ালি নথিভুক্ত।</p></div>';
}

/* ---------- XML sitemaps: general index and Google News (last 48 hours) ---------- */
add_action('init', function () {
    add_rewrite_rule('^news-sitemap\.xml$', 'index.php?tb_news_sitemap=1', 'top');
    add_rewrite_tag('%tb_news_sitemap%', '1');
}, 20);
add_action('template_redirect', function () {
    if (!get_query_var('tb_news_sitemap')) return;
    if (defined('WPSEO_VERSION') || class_exists('RankMath') || defined('AIOSEO_VERSION')) {
        status_header(404); exit; // Let the active SEO plugin own sitemap endpoints.
    }
    $posts=get_posts([
        'post_type'=>'post','post_status'=>'publish','numberposts'=>1000,
        'date_query'=>[['after'=>'48 hours ago','inclusive'=>true]],
        'orderby'=>'post_date','order'=>'DESC'
    ]);
    nocache_headers(); header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">'."\n";
    foreach ($posts as $post) {
        $title=get_post_meta($post->ID,'_tb_seo_title',true) ?: get_the_title($post);
        $terms=get_the_category($post->ID); $genres=$terms ? implode(', ',wp_list_pluck($terms,'name')) : 'News';
        echo '<url><loc>'.esc_url(get_permalink($post)).'</loc><news:news><news:publication><news:name>'.esc_html(get_bloginfo('name')).'</news:name><news:language>'.esc_html(substr(get_locale(),0,2)).'</news:language></news:publication><news:publication_date>'.esc_html(get_post_time('c',true,$post)).'</news:publication_date><news:title>'.esc_html(wp_strip_all_tags($title)).'</news:title><news:keywords>'.esc_html($genres).'</news:keywords></news:news></url>'."\n";
    }
    echo '</urlset>'; exit;
}, 1);

/* Native WordPress sitemap is enabled by default; add explicit reference for crawlers. */
add_filter('robots_txt', function ($output, $public) {
    if ($public) {
        $output .= "\nSitemap: ".esc_url(home_url('/wp-sitemap.xml'))."\n";
        $output .= "Sitemap: ".esc_url(home_url('/news-sitemap.xml'))."\n";
    }
    return $output;
}, 10, 2);

/* Basic Article schema for news posts when no SEO plugin supplies its own. */
add_action('wp_head', function () {
    if (!is_singular('post') || defined('WPSEO_VERSION') || class_exists('RankMath') || defined('AIOSEO_VERSION')) return;
    $id=get_queried_object_id();
    $data=[
        '@context'=>'https://schema.org','@type'=>'NewsArticle',
        'headline'=>wp_strip_all_tags(get_the_title($id)),
        'datePublished'=>get_post_time('c',true,$id),
        'dateModified'=>get_post_modified_time('c',true,$id),
        'mainEntityOfPage'=>get_permalink($id),
        'publisher'=>['@type'=>'Organization','name'=>get_bloginfo('name')],
    ];
    if (has_post_thumbnail($id)) $data['image']=[get_the_post_thumbnail_url($id,'full')];
    echo '<script type="application/ld+json">'.wp_json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).'</script>'."\n";
}, 30);
