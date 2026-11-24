<?php get_template_part('templates/page', 'header'); ?>
<?php if (have_posts()) : ?>
	<?php while (have_posts()) : ?>
		<?php the_post(); ?>
		<?php get_template_part('templates/content', get_post_format()); ?>
	<?php endwhile; ?>
	<?php the_posts_navigation(); ?>
<?php else : ?>
	<p><?php esc_html_e('Sorry, no results were found.', 'base-wp-theme'); ?></p>
	<?php get_search_form(); ?>
<?php endif; ?>