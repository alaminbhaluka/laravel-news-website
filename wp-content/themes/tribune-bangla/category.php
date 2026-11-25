<?php get_header(); ?>
<main class="tb-container tb-archive">
    <header class="tb-section-heading">
        <p class="tb-eyebrow">সংবাদ বিভাগ</p>
        <h1><?php single_cat_title(); ?></h1>
        <?php if (category_description()) : ?><div class="tb-category-description"><?php echo wp_kses_post(category_description()); ?></div><?php endif; ?>
    </header>
    <?php if (have_posts()) : ?>
        <div class="tb-card-grid">
            <?php while (have_posts()) : the_post(); get_template_part('template-parts/card'); endwhile; ?>
        </div>
        <nav class="tb-pagination" aria-label="পৃষ্ঠা নেভিগেশন"><?php the_posts_pagination(['prev_text'=>'আগের পৃষ্ঠা','next_text'=>'পরের পৃষ্ঠা']); ?></nav>
    <?php else : ?>
        <p>এই বিভাগে এখনো কোনো সংবাদ প্রকাশিত হয়নি।</p>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
