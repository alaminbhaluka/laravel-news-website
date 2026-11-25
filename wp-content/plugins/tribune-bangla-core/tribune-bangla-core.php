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
