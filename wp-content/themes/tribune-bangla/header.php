<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<?php if (get_theme_mod('tb_show_topbar', 1)): ?><div class="tb-top"><div class="tb-wrap"><span><?php echo esc_html(wp_date('l, j F Y')); ?></span><span>সর্বশেষ সংবাদ • নির্ভরযোগ্য তথ্য</span></div></div><?php endif; ?>
<header class="tb-masthead"><div class="tb-wrap"><a class="tb-brand" href="<?php echo esc_url(home_url('/')); ?>">
<?php if(has_custom_logo()){the_custom_logo();} else { ?><span class="tb-brand-name">ট্রিবিউন <span>বাংলা</span></span><?php } ?>
<span class="tb-tagline"><?php echo esc_html(get_theme_mod('tb_tagline_text', 'সত্যের সন্ধানে, পাঠকের পাশে')); ?></span></a></div></header>
<nav class="tb-nav" id="tb-navigation"><div class="tb-wrap"><div class="tb-nav-inner"><button class="tb-mobile-toggle" type="button" aria-expanded="false" aria-controls="tb-primary-menu">☰ <span class="screen-reader-text">মেনু</span></button><div id="tb-primary-menu"><?php wp_nav_menu(['theme_location'=>'primary','container'=>false,'menu_class'=>'tb-menu','fallback_cb'=>'tribune_bangla_menu_fallback']); ?></div><div class="tb-search"><?php get_search_form(); ?></div></div></div></nav>
