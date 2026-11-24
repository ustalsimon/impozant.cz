<div class="container mx-auto px-4">
	<?php get_template_part('templates/page', 'header'); ?>
	<p><?php esc_html_e('The page may have moved or no longer exists.', 'base-wp-theme'); ?></p>
	<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Return home', 'base-wp-theme'); ?></a>
</div>