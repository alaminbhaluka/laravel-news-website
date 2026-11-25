<?php
if (!defined('ABSPATH')) exit;
function tribune_bangla_setup() {
    load_theme_textdomain('tribune-bangla', get_template_directory() . '/languages');
    add_theme_support('title-tag'); add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links'); add_theme_support('responsive-embeds');
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption','style','script']);
    add_theme_support('custom-logo', ['height'=>90,'width'=>360,'flex-height'=>true,'flex-width'=>true]);
    add_theme_support('custom-background');
    add_image_size('tb-featured', 900, 600, true);
    add_image_size('tb-card', 480, 320, true);
    register_nav_menus(['primary'=>__('প্রধান মেনু','tribune-bangla'),'footer'=>__('ফুটার মেনু','tribune-bangla')]);
}
add_action('after_setup_theme','tribune_bangla_setup');
function tribune_bangla_assets() {
    wp_enqueue_style('tribune-bangla-style', get_stylesheet_uri(), [], '1.0.0');
    wp_enqueue_script('tribune-bangla-script', get_template_directory_uri().'/assets/js/theme.js', [], '1.0.0', true);
}
add_action('wp_enqueue_scripts','tribune_bangla_assets');
function tribune_bangla_widgets() {
    register_sidebar(['name'=>__('হোমপেজ সাইডবার','tribune-bangla'),'id'=>'home-sidebar','before_widget'=>'<section class="widget">','after_widget'=>'</section>','before_title'=>'<h2 class="widget-title">','after_title'=>'</h2>']);
    register_sidebar(['name'=>__('ফুটার উইজেট','tribune-bangla'),'id'=>'footer-widgets','before_widget'=>'<section class="widget">','after_widget'=>'</section>','before_title'=>'<h2 class="widget-title">','after_title'=>'</h2>']);
}
add_action('widgets_init','tribune_bangla_widgets');
function tribune_bangla_excerpt_length($length){return 24;}
add_filter('excerpt_length','tribune_bangla_excerpt_length');
function tribune_bangla_excerpt_more(){return '…';}
add_filter('excerpt_more','tribune_bangla_excerpt_more');
function tribune_bangla_post_meta(){echo '<span class="tb-meta">'.esc_html(get_the_date('j F Y')).'</span>';}
function tribune_bangla_category_label(){ $cats=get_the_category(); if($cats){echo '<a class="tb-cat" href="'.esc_url(get_category_link($cats[0]->term_id)).'">'.esc_html($cats[0]->name).'</a>';}}
function tribune_bangla_menu_fallback(){echo '<ul class="tb-menu"><li><a href="'.esc_url(home_url('/')).'">প্রচ্ছদ</a></li>'; wp_list_categories(['title_li'=>'','depth'=>1]); echo '</ul>';}
function tribune_bangla_customizer($wp_customize){
    $wp_customize->add_section('tb_news_options',['title'=>__('ট্রিবিউন বাংলা: সংবাদ সেটিংস','tribune-bangla'),'priority'=>35]);
    $wp_customize->add_setting('tb_breaking_label',['default'=>'ব্রেকিং নিউজ','sanitize_callback'=>'sanitize_text_field']);
    $wp_customize->add_control('tb_breaking_label',['label'=>__('ব্রেকিং নিউজ শিরোনাম','tribune-bangla'),'section'=>'tb_news_options','type'=>'text']);
    $wp_customize->add_setting('tb_footer_text',['default'=>'সত্যের সন্ধানে, পাঠকের পাশে','sanitize_callback'=>'sanitize_text_field']);
    $wp_customize->add_control('tb_footer_text',['label'=>__('ফুটার পরিচিতি','tribune-bangla'),'section'=>'tb_news_options','type'=>'text']);
}
add_action('customize_register','tribune_bangla_customizer');


/* সম্পাদকীয় নিয়ন্ত্রণ: ব্রেকিং নিউজ */
function tribune_bangla_add_breaking_metabox() {
    add_meta_box('tb-breaking-news', __('সংবাদটি ব্রেকিং নিউজ হিসেবে দেখান','tribune-bangla'), 'tribune_bangla_breaking_metabox_html', 'post', 'side', 'high');
}
add_action('add_meta_boxes', 'tribune_bangla_add_breaking_metabox');
function tribune_bangla_breaking_metabox_html($post) {
    wp_nonce_field('tb_save_breaking_news', 'tb_breaking_nonce');
    $checked = get_post_meta($post->ID, '_tb_breaking', true) === '1';
    echo '<label><input type="checkbox" name="tb_breaking" value="1" '.checked($checked, true, false).' /> '.esc_html__('হোমপেজের ব্রেকিং নিউজ বারে দেখান','tribune-bangla').'</label>';
}
function tribune_bangla_save_breaking_metabox($post_id) {
    if (!isset($_POST['tb_breaking_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tb_breaking_nonce'])), 'tb_save_breaking_news')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (isset($_POST['tb_breaking']) && $_POST['tb_breaking'] === '1') {
        update_post_meta($post_id, '_tb_breaking', '1');
    } else {
        delete_post_meta($post_id, '_tb_breaking');
    }
}
add_action('save_post_post', 'tribune_bangla_save_breaking_metabox');

/* বাংলা তারিখ ও সময় প্রদর্শনের জন্য সাইট লোকেল অনুসরণ */
function tribune_bangla_body_classes($classes) {
    $classes[] = 'tb-theme';
    if (is_front_page()) $classes[] = 'tb-home';
    return $classes;
}
add_filter('body_class', 'tribune_bangla_body_classes');
