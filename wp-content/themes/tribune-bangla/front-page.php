<?php get_header(); ?>
<main class="tb-wrap">
<?php $breaking=new WP_Query(['post_type'=>'post','posts_per_page'=>6,'meta_key'=>'_tb_breaking','meta_value'=>'1']); ?>
<?php if($breaking->have_posts()): ?><div class="tb-breaking"><strong><?php echo esc_html(get_theme_mod('tb_breaking_label','ব্রেকিং নিউজ')); ?></strong><div><?php while($breaking->have_posts()):$breaking->the_post(); ?><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>　•　<?php endwhile; ?></div></div><?php endif; wp_reset_postdata(); ?>
<?php $hero=new WP_Query(['post_type'=>'post','posts_per_page'=>5,'ignore_sticky_posts'=>false]); if($hero->have_posts()): $hero->the_post(); ?>
<section class="tb-hero tb-section"><article class="tb-hero-main"><a href="<?php the_permalink(); ?>"><?php if(has_post_thumbnail())the_post_thumbnail('large'); ?><div class="tb-overlay"><?php tribune_bangla_category_label(); ?><h1><?php the_title(); ?></h1><?php tribune_bangla_post_meta(); ?></div></a></article><div class="tb-hero-side"><?php while($hero->have_posts()):$hero->the_post(); ?><article class="tb-hero-small"><a href="<?php the_permalink(); ?>"><?php if(has_post_thumbnail())the_post_thumbnail('medium_large'); ?><div class="tb-overlay"><?php tribune_bangla_category_label(); ?><h2><?php the_title(); ?></h2></div></a></article><?php endwhile; ?></div></section><?php endif; wp_reset_postdata(); ?>
<?php
$cats = get_categories(['hide_empty'=>true, 'number'=>0]);
$tb_category_order = [
    'national','bangladesh','country','saradesh','politics','international',
    'economy','business','sports','entertainment','education','technology',
    'lifestyle','health','crime','district','mymensingh','valuka'
];
$tb_category_name_order = ['জাতীয়','বাংলাদেশ','সারাদেশ','রাজনীতি','আন্তর্জাতিক','অর্থনীতি','ব্যবসা-বাণিজ্য','খেলাধুলা','ক্রীড়া','বিনোদন','শিক্ষা','প্রযুক্তি','জীবনযাপন','স্বাস্থ্য','অপরাধ','জেলা','ময়মনসিংহ','ভালুকা'];
$cats = array_values(array_filter($cats, function($cat){ return $cat->slug !== 'uncategorized'; }));
usort($cats, function($a, $b) use ($tb_category_order, $tb_category_name_order) {
    $a_slug = array_search($a->slug, $tb_category_order, true);
    $b_slug = array_search($b->slug, $tb_category_order, true);
    $a_name = array_search($a->name, $tb_category_name_order, true);
    $b_name = array_search($b->name, $tb_category_name_order, true);
    $a_rank = $a_slug !== false ? $a_slug : ($a_name !== false ? $a_name : 999);
    $b_rank = $b_slug !== false ? $b_slug : ($b_name !== false ? $b_name : 999);
    return $a_rank === $b_rank ? strcasecmp($a->name, $b->name) : ($a_rank <=> $b_rank);
});
$cats = array_slice($cats, 0, max(1, min(12, (int)get_theme_mod('tb_home_category_count', 8))));
foreach($cats as $cat):
$q = new WP_Query(['cat'=>$cat->term_id, 'posts_per_page'=>4]);
if($q->have_posts()): ?>
<section class="tb-section tb-category-section">
<div class="tb-section-head"><h2><?php echo esc_html($cat->name); ?></h2><a href="<?php echo esc_url(get_category_link($cat)); ?>">সব খবর দেখুন <span aria-hidden="true">→</span></a></div>
<div class="tb-grid"><?php while($q->have_posts()): $q->the_post(); get_template_part('template-parts/card'); endwhile; ?></div>
</section>
<?php endif; wp_reset_postdata(); endforeach; ?>
<section class="tb-section tb-layout"><div><div class="tb-section-head"><h2>সর্বশেষ সংবাদ</h2></div><div class="tb-grid"><?php $latest=new WP_Query(['posts_per_page'=>8,'offset'=>5]); while($latest->have_posts()):$latest->the_post(); get_template_part('template-parts/card'); endwhile; wp_reset_postdata(); ?></div></div><aside class="tb-sidebar"><?php if(is_active_sidebar('home-sidebar')): dynamic_sidebar('home-sidebar'); else: ?><section><h2>জনপ্রিয় সংবাদ</h2><ul class="tb-list"><?php $popular=new WP_Query(['posts_per_page'=>5]);while($popular->have_posts()):$popular->the_post(); ?><li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li><?php endwhile;wp_reset_postdata(); ?></ul></section><?php endif; ?></aside></section>
</main><?php get_footer(); ?>
