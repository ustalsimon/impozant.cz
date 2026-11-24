<?php $site_name = get_bloginfo('name'); ?>
<a class="screen-reader-text skip-link" href="#main-content"><?php esc_html_e('Skip to content', 'base-wp-theme'); ?></a>
<header class="" role="banner">
	<div class="px-24 max-w-page mx-auto flex items-center justify-between gap-4 max-lg:px-16 max-md:px-8 max-sm:px-4">
		<a class="" href="<?php echo esc_url(home_url('/')); ?>">
			<?php echo esc_html($site_name); ?>
		</a>

		<div class="flex items-center gap-4">
			<div class="main-nav">
				<?php
				if (has_nav_menu('primary_navigation')) {
					wp_nav_menu(array('theme_location' => 'primary_navigation', 'container' => 'nav', 'container_aria_label' => __('Primary Navigation', 'base-wp-theme'), 'menu_class' => 'flex flex-wrap items-center gap-6 text-ink-primary'));
				}
				?>
			</div>
		</div>
	</div>
</header>
