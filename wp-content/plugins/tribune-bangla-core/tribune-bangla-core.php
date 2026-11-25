<?php
/**
 * Plugin Name: Tribune Bangla Core
 * Description: Core editorial features for Tribune Bangla: galleries, advertisements, subscribers, contact messages and reporter mobile profiles.
 * Version: 1.0.0
 * Author: Tribune Bangla
 * Text Domain: tribune-bangla-core
 */
if (!defined('ABSPATH')) exit;

final class Tribune_Bangla_Core {
    public function __construct() {
        add_action('init', [$this, 'register_content_types']);
        add_action('show_user_profile', [$this, 'mobile_profile_field']);
        add_action('edit_user_profile', [$this, 'mobile_profile_field']);
        add_action('personal_options_update', [$this, 'save_mobile_profile_field']);
        add_action('edit_user_profile_update', [$this, 'save_mobile_profile_field']);
        add_shortcode('tribune_newsletter', [$this, 'newsletter_form']);
        add_shortcode('tribune_contact_form', [$this, 'contact_form']);
        add_shortcode('tribune_mobile_account', [$this, 'mobile_account_form']);
        add_action('admin_post_nopriv_tb_mobile_login', [$this, 'mobile_login']);
        add_action('admin_post_nopriv_tb_mobile_register', [$this, 'mobile_register']);
        add_action('admin_post_nopriv_tb_subscribe', [$this, 'subscribe']);
        add_action('admin_post_tb_subscribe', [$this, 'subscribe']);
        add_action('admin_post_nopriv_tb_contact', [$this, 'contact_submit']);
        add_action('admin_post_tb_contact', [$this, 'contact_submit']);
        add_action('admin_menu', [$this, 'settings_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_content_types() {
        register_post_type('tb_gallery', [
            'labels' => ['name' => 'ফটো ও ভিডিও গ্যালারি', 'singular_name' => 'গ্যালারি', 'add_new_item' => 'নতুন গ্যালারি যোগ করুন'],
            'public' => true, 'show_in_rest' => true, 'menu_icon' => 'dashicons-format-gallery',
            'supports' => ['title','editor','thumbnail','excerpt'], 'has_archive' => true,
            'rewrite' => ['slug' => 'gallery']
        ]);
        register_post_type('tb_advertisement', [
            'labels' => ['name' => 'বিজ্ঞাপন', 'singular_name' => 'বিজ্ঞাপন', 'add_new_item' => 'নতুন বিজ্ঞাপন যোগ করুন'],
            'public' => false, 'show_ui' => true, 'show_in_rest' => true, 'menu_icon' => 'dashicons-megaphone',
            'supports' => ['title','editor','thumbnail','page-attributes']
        ]);
        register_post_type('tb_subscriber', [
            'labels' => ['name' => 'নিউজলেটার গ্রাহক', 'singular_name' => 'গ্রাহক'],
            'public' => false, 'show_ui' => true, 'show_in_rest' => false, 'menu_icon' => 'dashicons-email-alt',
            'supports' => ['title']
        ]);
        register_post_type('tb_contact_message', [
            'labels' => ['name' => 'যোগাযোগের বার্তা', 'singular_name' => 'বার্তা'],
            'public' => false, 'show_ui' => true, 'show_in_rest' => false, 'menu_icon' => 'dashicons-email',
            'supports' => ['title','editor']
        ]);
    }

    public function mobile_profile_field($user) {
        if (!current_user_can('edit_user', $user->ID)) return;
        ?>
        <h2>ট্রিবিউন বাংলা — প্রতিবেদক তথ্য</h2>
        <table class="form-table"><tr>
            <th><label for="tb_mobile">মোবাইল নম্বর</label></th>
            <td><input type="tel" name="tb_mobile" id="tb_mobile" value="<?php echo esc_attr(get_user_meta($user->ID, 'tb_mobile', true)); ?>" class="regular-text" /></td>
        </tr></table>
        <?php
    }

    public function save_mobile_profile_field($user_id) {
        if (!current_user_can('edit_user', $user_id)) return;
        if (isset($_POST['tb_mobile'])) {
            update_user_meta($user_id, 'tb_mobile', sanitize_text_field(wp_unslash($_POST['tb_mobile'])));
        }
    }

    public function newsletter_form() {
        $action = esc_url(admin_url('admin-post.php'));
        return '<form class="tb-newsletter-form" method="post" action="'.$action.'">
            <input type="hidden" name="action" value="tb_subscribe">
            '.wp_nonce_field('tb_subscribe', 'tb_subscribe_nonce', true, false).'
            <label class="screen-reader-text" for="tb_subscriber_email">ইমেইল</label>
            <input id="tb_subscriber_email" type="email" name="email" placeholder="আপনার ইমেইল ঠিকানা" required>
            <button type="submit">সাবস্ক্রাইব</button>
        </form>';
    }

    public function subscribe() {
        if (!isset($_POST['tb_subscribe_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tb_subscribe_nonce'])), 'tb_subscribe')) {
            wp_die('অনুরোধটি যাচাই করা যায়নি।');
        }
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        if (!$email || !is_email($email)) wp_die('সঠিক ইমেইল ঠিকানা দিন।');
        $exists = get_posts(['post_type'=>'tb_subscriber','post_status'=>'private','numberposts'=>1,'meta_key'=>'tb_email','meta_value'=>$email]);
        if (!$exists) {
            $id = wp_insert_post(['post_type'=>'tb_subscriber','post_status'=>'private','post_title'=>$email]);
            if ($id && !is_wp_error($id)) update_post_meta($id, 'tb_email', $email);
        }
        wp_safe_redirect(add_query_arg('subscribed', '1', wp_get_referer() ?: home_url('/')));
        exit;
    }

    public function mobile_account_form() {
        if (is_user_logged_in()) return '<p>আপনি লগইন অবস্থায় আছেন। <a href="'.esc_url(wp_logout_url(home_url('/'))).'">লগআউট</a></p>';
        $action = esc_url(admin_url('admin-post.php'));
        $nonce_login = wp_nonce_field('tb_mobile_login', 'tb_mobile_login_nonce', true, false);
        $nonce_register = wp_nonce_field('tb_mobile_register', 'tb_mobile_register_nonce', true, false);
        return '<div class="tb-account-forms"><form method="post" action="'.$action.'"><h2>মোবাইল নম্বরে লগইন</h2>
            <input type="hidden" name="action" value="tb_mobile_login">'.$nonce_login.'
            <p><label>মোবাইল নম্বর <input type="tel" name="mobile" inputmode="numeric" required></label></p>
            <p><label>পাসওয়ার্ড <input type="password" name="password" required></label></p>
            <button type="submit">লগইন</button></form>
            <form method="post" action="'.$action.'"><h2>নতুন অ্যাকাউন্ট</h2>
            <input type="hidden" name="action" value="tb_mobile_register">'.$nonce_register.'
            <p><label>নাম <input type="text" name="name" required></label></p>
            <p><label>মোবাইল নম্বর <input type="tel" name="mobile" inputmode="numeric" required></label></p>
            <p><label>পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর) <input type="password" name="password" minlength="8" required></label></p>
            <button type="submit">নিবন্ধন</button></form></div>';
    }

    private function normalize_mobile($mobile) {
        $digits = preg_replace('/\\D+/', '', $mobile);
        if (strpos($digits, '880') === 0) $digits = '0'.substr($digits, 3);
        return preg_match('/^01[3-9][0-9]{8}$/', $digits) ? $digits : '';
    }

    public function mobile_login() {
        if (!isset($_POST['tb_mobile_login_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tb_mobile_login_nonce'])), 'tb_mobile_login')) wp_die('অনুরোধটি যাচাই করা যায়নি।');
        $mobile = $this->normalize_mobile(isset($_POST['mobile']) ? wp_unslash($_POST['mobile']) : '');
        $password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
        $users = $mobile ? get_users(['meta_key'=>'tb_mobile','meta_value'=>$mobile,'number'=>1]) : [];
        if (!$users || !wp_check_password($password, $users[0]->user_pass, $users[0]->ID)) wp_die('মোবাইল নম্বর অথবা পাসওয়ার্ড সঠিক নয়।');
        wp_set_current_user($users[0]->ID);
        wp_set_auth_cookie($users[0]->ID, true, is_ssl());
        do_action('wp_login', $users[0]->user_login, $users[0]);
        wp_safe_redirect(home_url('/'));
        exit;
    }

    public function mobile_register() {
        if (!isset($_POST['tb_mobile_register_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tb_mobile_register_nonce'])), 'tb_mobile_register')) wp_die('অনুরোধটি যাচাই করা যায়নি।');
        if (!get_option('users_can_register')) wp_die('এই মুহূর্তে নিবন্ধন বন্ধ রয়েছে।');
        $mobile = $this->normalize_mobile(isset($_POST['mobile']) ? wp_unslash($_POST['mobile']) : '');
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
        if (!$mobile || !$name || strlen($password) < 8) wp_die('নাম, সঠিক বাংলাদেশি মোবাইল নম্বর এবং কমপক্ষে ৮ অক্ষরের পাসওয়ার্ড দিন।');
        if (get_users(['meta_key'=>'tb_mobile','meta_value'=>$mobile,'number'=>1])) wp_die('এই মোবাইল নম্বর দিয়ে আগে থেকেই অ্যাকাউন্ট রয়েছে।');
        $username = 'tb_'.substr($mobile, 1);
        if (username_exists($username)) wp_die('এই নম্বরের অ্যাকাউন্ট আগে থেকেই রয়েছে।');
        $user_id = wp_create_user($username, $password);
        if (is_wp_error($user_id)) wp_die('অ্যাকাউন্ট তৈরি করা যায়নি।');
        wp_update_user(['ID'=>$user_id,'display_name'=>$name,'role'=>'subscriber']);
        update_user_meta($user_id, 'tb_mobile', $mobile);
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true, is_ssl());
        wp_safe_redirect(home_url('/'));
        exit;
    }

    public function contact_form() {
        $action = esc_url(admin_url('admin-post.php'));
        return '<form class="tb-contact-form" method="post" action="'.$action.'">
            <input type="hidden" name="action" value="tb_contact">
            '.wp_nonce_field('tb_contact', 'tb_contact_nonce', true, false).'
            <p><label>আপনার নাম <input type="text" name="name" required></label></p>
            <p><label>ইমেইল <input type="email" name="email"></label></p>
            <p><label>বার্তা <textarea name="message" rows="6" required></textarea></label></p>
            <button type="submit">বার্তা পাঠান</button>
        </form>';
    }

    public function contact_submit() {
        if (!isset($_POST['tb_contact_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tb_contact_nonce'])), 'tb_contact')) {
            wp_die('অনুরোধটি যাচাই করা যায়নি।');
        }
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
        if (!$name || !$message || ($email && !is_email($email))) wp_die('প্রয়োজনীয় তথ্য সঠিকভাবে পূরণ করুন।');
        $id = wp_insert_post(['post_type'=>'tb_contact_message','post_status'=>'private','post_title'=>$name.' — '.current_time('Y-m-d H:i'),'post_content'=>$message]);
        if ($id && !is_wp_error($id)) update_post_meta($id, 'tb_sender_email', $email);
        wp_safe_redirect(add_query_arg('message_sent', '1', wp_get_referer() ?: home_url('/')));
        exit;
    }

    public function settings_menu() {
        add_options_page('ট্রিবিউন বাংলা সেটিংস', 'ট্রিবিউন বাংলা', 'manage_options', 'tribune-bangla-core', [$this, 'settings_page']);
    }

    public function register_settings() {
        register_setting('tb_core_settings', 'tb_contact_email', ['sanitize_callback'=>'sanitize_email']);
        register_setting('tb_core_settings', 'tb_contact_phone', ['sanitize_callback'=>'sanitize_text_field']);
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap"><h1>ট্রিবিউন বাংলা সেটিংস</h1>
        <form method="post" action="options.php">
            <?php settings_fields('tb_core_settings'); ?>
            <table class="form-table">
                <tr><th><label for="tb_contact_email">যোগাযোগের ইমেইল</label></th><td><input class="regular-text" type="email" id="tb_contact_email" name="tb_contact_email" value="<?php echo esc_attr(get_option('tb_contact_email','')); ?>"></td></tr>
                <tr><th><label for="tb_contact_phone">যোগাযোগের ফোন</label></th><td><input class="regular-text" type="text" id="tb_contact_phone" name="tb_contact_phone" value="<?php echo esc_attr(get_option('tb_contact_phone','')); ?>"></td></tr>
            </table>
            <?php submit_button('সেটিংস সংরক্ষণ'); ?>
        </form>
        <p>নিউজলেটার ফর্ম দেখাতে <code>[tribune_newsletter]</code> শর্টকোড ব্যবহার করুন।</p></div>
        <?php
    }
}
new Tribune_Bangla_Core();


/* বিজ্ঞাপন ও গ্যালারির জন্য সম্পাদকীয় মেটাডেটা */
function tb_core_add_meta_boxes() {
    add_meta_box('tb-ad-details', 'বিজ্ঞাপনের প্রদর্শন সেটিংস', 'tb_core_ad_box', 'tb_advertisement', 'normal', 'high');
    add_meta_box('tb-gallery-details', 'গ্যালারি সেটিংস', 'tb_core_gallery_box', 'tb_gallery', 'normal', 'high');
}
add_action('add_meta_boxes', 'tb_core_add_meta_boxes');

function tb_core_ad_box($post) {
    wp_nonce_field('tb_core_save_meta', 'tb_core_meta_nonce');
    $fields = [
        'tb_ad_position' => ['label'=>'অবস্থান', 'type'=>'select', 'options'=>['header'=>'হেডার','homepage'=>'হোমপেজ','sidebar'=>'সাইডবার','article'=>'সংবাদের ভেতরে','footer'=>'ফুটার']],
        'tb_ad_url' => ['label'=>'বিজ্ঞাপনে ক্লিকের লিংক', 'type'=>'url'],
        'tb_ad_start' => ['label'=>'শুরুর তারিখ', 'type'=>'date'],
        'tb_ad_end' => ['label'=>'শেষের তারিখ', 'type'=>'date'],
    ];
    foreach ($fields as $key=>$field) {
        $value = get_post_meta($post->ID, $key, true);
        echo '<p><label><strong>'.esc_html($field['label']).'</strong><br>';
        if ($field['type']==='select') {
            echo '<select name="'.esc_attr($key).'">';
            foreach ($field['options'] as $v=>$label) echo '<option value="'.esc_attr($v).'" '.selected($value,$v,false).'>'.esc_html($label).'</option>';
            echo '</select>';
        } else {
            echo '<input class="regular-text" type="'.esc_attr($field['type']).'" name="'.esc_attr($key).'" value="'.esc_attr($value).'">';
        }
        echo '</label></p>';
    }
    echo '<p>বিজ্ঞাপনের ছবি ফিচার্ড ইমেজ হিসেবে দিন। চাইলে সম্পাদনা অংশে বিজ্ঞাপনের HTML/লেখাও যোগ করতে পারবেন।</p>';
}
function tb_core_gallery_box($post) {
    wp_nonce_field('tb_core_save_meta', 'tb_core_meta_nonce');
    $video = get_post_meta($post->ID, 'tb_gallery_video_url', true);
    echo '<p><label><strong>ভিডিও URL (ঐচ্ছিক)</strong><br><input class="large-text" type="url" name="tb_gallery_video_url" value="'.esc_attr($video).'" placeholder="https://..."></label></p>';
    echo '<p>একাধিক ছবি যোগ করতে মূল সম্পাদনা অংশে গ্যালারির ছবি যুক্ত করুন অথবা ফিচার্ড ইমেজ নির্বাচন করুন।</p>';
}
function tb_core_save_editorial_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id) || !isset($_POST['tb_core_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tb_core_meta_nonce'])), 'tb_core_save_meta')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (get_post_type($post_id)==='tb_advertisement') {
        $position = isset($_POST['tb_ad_position']) ? sanitize_key(wp_unslash($_POST['tb_ad_position'])) : 'homepage';
        $allowed = ['header','homepage','sidebar','article','footer'];
        if (in_array($position,$allowed,true)) update_post_meta($post_id,'tb_ad_position',$position);
        foreach (['tb_ad_url'=>'esc_url_raw','tb_ad_start'=>'sanitize_text_field','tb_ad_end'=>'sanitize_text_field'] as $key=>$sanitize) {
            if (isset($_POST[$key])) update_post_meta($post_id,$key,call_user_func($sanitize,wp_unslash($_POST[$key])));
        }
    }
    if (get_post_type($post_id)==='tb_gallery' && isset($_POST['tb_gallery_video_url'])) {
        update_post_meta($post_id,'tb_gallery_video_url',esc_url_raw(wp_unslash($_POST['tb_gallery_video_url'])));
    }
}
add_action('save_post_tb_advertisement','tb_core_save_editorial_meta');
add_action('save_post_tb_gallery','tb_core_save_editorial_meta');

/* নির্ধারিত স্থান ও সময় অনুযায়ী সক্রিয় বিজ্ঞাপন */
function tb_core_ads_shortcode($atts) {
    $atts = shortcode_atts(['position'=>'homepage','limit'=>3],$atts,'tribune_ads');
    $position = sanitize_key($atts['position']);
    if (!in_array($position,['header','homepage','sidebar','article','footer'],true)) return '';
    $today = current_time('Y-m-d');
    $query = new WP_Query(['post_type'=>'tb_advertisement','post_status'=>'publish','posts_per_page'=>max(1,min(10,(int)$atts['limit'])),'meta_key'=>'tb_ad_position','meta_value'=>$position,'orderby'=>'menu_order date','order'=>'ASC']);
    if (!$query->have_posts()) return '';
    ob_start();
    echo '<div class="tb-advertisements tb-ad-'.esc_attr($position).'">';
    while ($query->have_posts()) {
        $query->the_post();
        $start=get_post_meta(get_the_ID(),'tb_ad_start',true);
        $end=get_post_meta(get_the_ID(),'tb_ad_end',true);
        if (($start && $start>$today) || ($end && $end<$today)) continue;
        $url=get_post_meta(get_the_ID(),'tb_ad_url',true);
        echo '<div class="tb-ad-item">';
        if ($url) echo '<a href="'.esc_url($url).'" target="_blank" rel="sponsored noopener">';
        if (has_post_thumbnail()) the_post_thumbnail('large');
        if (trim(get_the_content())) echo '<div class="tb-ad-copy">'.wp_kses_post(apply_filters('the_content',get_the_content())).'</div>';
        if ($url) echo '</a>';
        echo '</div>';
    }
    echo '</div>';
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('tribune_ads','tb_core_ads_shortcode');

/* গ্যালারি তালিকা */
function tb_core_gallery_shortcode($atts) {
    $atts=shortcode_atts(['limit'=>12],$atts,'tribune_gallery');
    $q=new WP_Query(['post_type'=>'tb_gallery','post_status'=>'publish','posts_per_page'=>max(1,min(40,(int)$atts['limit']))]);
    if (!$q->have_posts()) return '<p>এখনো কোনো গ্যালারি প্রকাশ করা হয়নি।</p>';
    ob_start();
    echo '<div class="tb-gallery-grid">';
    while($q->have_posts()) {
        $q->the_post();
        echo '<article class="tb-gallery-card"><a href="'.esc_url(get_permalink()).'">';
        if(has_post_thumbnail()) the_post_thumbnail('medium_large');
        echo '<h3>'.esc_html(get_the_title()).'</h3></a>';
        $video=get_post_meta(get_the_ID(),'tb_gallery_video_url',true);
        if($video) echo '<p><a href="'.esc_url($video).'" target="_blank" rel="noopener">ভিডিও দেখুন</a></p>';
        echo '</article>';
    }
    echo '</div>';
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('tribune_gallery','tb_core_gallery_shortcode');

/* সম্পাদকীয় দলের জন্য সীমিত ক্ষমতার আলাদা ভূমিকা */
function tb_core_register_editorial_roles() {
    add_role('tb_reporter','প্রতিবেদক',['read'=>true,'edit_posts'=>true,'publish_posts'=>false,'delete_posts'=>false,'upload_files'=>true]);
    add_role('tb_sub_editor','সহ-সম্পাদক',['read'=>true,'edit_posts'=>true,'edit_others_posts'=>true,'publish_posts'=>true,'upload_files'=>true,'moderate_comments'=>true]);
}
add_action('init','tb_core_register_editorial_roles');



/* প্রতিটি সংবাদ ও পাতার SEO তথ্য */
function tb_core_seo_box() {
    foreach (['post','page'] as $screen) add_meta_box('tb-seo-fields','SEO সেটিংস','tb_core_seo_box_html',$screen,'normal','low');
}
add_action('add_meta_boxes','tb_core_seo_box');
function tb_core_seo_box_html($post) {
    wp_nonce_field('tb_core_seo_save','tb_core_seo_nonce');
    $description=get_post_meta($post->ID,'_tb_seo_description',true);
    $canonical=get_post_meta($post->ID,'_tb_seo_canonical',true);
    $noindex=get_post_meta($post->ID,'_tb_seo_noindex',true);
    echo '<p><label><strong>মেটা বিবরণ (সর্বোচ্চ ১৬০ অক্ষর)</strong><br><textarea class="widefat" rows="3" maxlength="160" name="tb_seo_description">'.esc_textarea($description).'</textarea></label></p>';
    echo '<p><label><strong>ক্যানোনিক্যাল URL (ঐচ্ছিক)</strong><br><input class="widefat" type="url" name="tb_seo_canonical" value="'.esc_attr($canonical).'"></label></p>';
    echo '<p><label><input type="checkbox" name="tb_seo_noindex" value="1" '.checked($noindex,'1',false).'> সার্চ ইঞ্জিনে এই পেজ ইনডেক্স করবেন না</label></p>';
}
function tb_core_save_seo($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id) || !isset($_POST['tb_core_seo_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tb_core_seo_nonce'])),'tb_core_seo_save')) return;
    if (!current_user_can('edit_post',$post_id)) return;
    $description=isset($_POST['tb_seo_description']) ? sanitize_textarea_field(wp_unslash($_POST['tb_seo_description'])) : '';
    $canonical=isset($_POST['tb_seo_canonical']) ? esc_url_raw(wp_unslash($_POST['tb_seo_canonical'])) : '';
    if ($description) update_post_meta($post_id,'_tb_seo_description',mb_substr($description,0,160)); else delete_post_meta($post_id,'_tb_seo_description');
    if ($canonical) update_post_meta($post_id,'_tb_seo_canonical',$canonical); else delete_post_meta($post_id,'_tb_seo_canonical');
    if (isset($_POST['tb_seo_noindex'])) update_post_meta($post_id,'_tb_seo_noindex','1'); else delete_post_meta($post_id,'_tb_seo_noindex');
}
add_action('save_post_post','tb_core_save_seo');
add_action('save_post_page','tb_core_save_seo');
function tb_core_output_seo() {
    if (!is_singular()) return;
    $id=get_queried_object_id();
    if (!$id) return;
    $description=get_post_meta($id,'_tb_seo_description',true);
    $canonical=get_post_meta($id,'_tb_seo_canonical',true);
    $noindex=get_post_meta($id,'_tb_seo_noindex',true);
    if ($description) echo '<meta name="description" content="'.esc_attr($description).'">'.PHP_EOL;
    if ($canonical) echo '<link rel="canonical" href="'.esc_url($canonical).'">'.PHP_EOL;
    if ($noindex==='1') echo '<meta name="robots" content="noindex,follow">'.PHP_EOL;
    if (has_post_thumbnail($id)) {
        $image=get_the_post_thumbnail_url($id,'large');
        if ($image) echo '<meta property="og:image" content="'.esc_url($image).'">'.PHP_EOL;
    }
    echo '<meta property="og:title" content="'.esc_attr(get_the_title($id)).'">'.PHP_EOL;
    if ($description) echo '<meta property="og:description" content="'.esc_attr($description).'">'.PHP_EOL;
    echo '<meta property="og:type" content="article">'.PHP_EOL;
}
add_action('wp_head','tb_core_output_seo',2);

/* সম্পাদকীয় ভূমিকার ক্ষমতা স্পষ্টভাবে নির্ধারণ */
function tb_core_sync_role_caps() {
    $reporter=get_role('tb_reporter');
    if ($reporter) { $reporter->add_cap('read'); $reporter->add_cap('edit_posts'); $reporter->add_cap('upload_files'); }
    $sub=get_role('tb_sub_editor');
    if ($sub) foreach (['read','edit_posts','edit_others_posts','edit_published_posts','publish_posts','upload_files','moderate_comments'] as $cap) $sub->add_cap($cap);
}
add_action('init','tb_core_sync_role_caps',20);
