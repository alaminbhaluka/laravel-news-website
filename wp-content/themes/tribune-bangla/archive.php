<?php get_header(); ?>
<main class="tb-container tb-archive">
    <header class="tb-section-heading">
        <p class="tb-eyebrow">সংবাদ আর্কাইভ</p>
        <h1><?php the_archive_title(); ?></h1>
        <?php the_archive_description('<div class="tb-category-description">','</div>'); ?>
    </header>
    <?php if (have_posts()) : ?>
        <div class="tb-card-grid">
            <?php while (have_posts()) : the_post(); get_template_part('template-parts/card'); endwhile; ?>
        </div>
        <nav class="tb-pagination" aria-label="পৃষ্ঠা নেভিগেশন"><?php the_posts_pagination(['prev_text'=>'আগের পৃষ্ঠা','next_text'=>'পরের পৃষ্ঠা']); ?></nav>
    <?php else : ?>
        <p>কোনো সংবাদ পাওয়া যায়নি।</p>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
