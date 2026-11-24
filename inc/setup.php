<?php

/**
 * Theme feature, menu and widget registration.
 *
 * @package Base WP Theme
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Register theme supports and navigation locations.
 */
function theme_setup(): void
{
	load_theme_textdomain('base-wp-theme', get_theme_file_path('/lang'));

	register_nav_menus(
		array(
			'primary_navigation' => __('Primary Navigation', 'base-wp-theme'),
			'footer_navigation'  => __('Footer Navigation', 'base-wp-theme'),
		)
	);

	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('responsive-embeds');
	add_theme_support('wp-block-styles');
	add_theme_support(
		'html5',
		array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script')
	);
}
add_action('after_setup_theme', 'theme_setup');

/**
 * Register the existing sidebar.
 */
function theme_widgets_init(): void
{
	register_sidebar(
		array(
			'name'          => __('Primary Sidebar', 'base-wp-theme'),
			'id'            => 'sidebar-primary',
			'before_widget' => '<section class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);
}
add_action('widgets_init', 'theme_widgets_init');

/**
 * Set the maximum width of unbounded content for legacy embeds.
 */
function theme_set_content_width(): void
{
	$GLOBALS['content_width'] = 1200;
}
add_action('after_setup_theme', 'theme_set_content_width', 0);
