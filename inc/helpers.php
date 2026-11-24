<?php

/**
 * Shared presentation helpers.
 *
 * @package Base WP Theme
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Read an SCF value without making the theme depend on the plugin.
 *
 * @param string       $selector Field name or key.
 * @param int|string|false $post_id Optional post or options identifier.
 * @param mixed        $default  Value returned for an unavailable or empty field.
 * @return mixed
 */
function theme_get_field(string $selector, $post_id = false, $default = null)
{
	if (! function_exists('get_field')) {
		return $default;
	}

	$value = get_field($selector, $post_id);

	return null === $value || false === $value || '' === $value ? $default : $value;
}

/**
 * Render an SCF/WordPress image with responsive sources and intrinsic dimensions.
 *
 * SCF image fields may return an attachment ID, an attachment array, or a URL.
 * Attachment-backed images use WordPress' generated srcset and sizes metadata.
 *
 * @param mixed                $image Image field value.
 * @param string|array<int>    $size  Registered image size or dimensions.
 * @param array<string, mixed> $attrs HTML attributes.
 */
function theme_get_image_html($image, $size = 'large', array $attrs = array()): string
{
	if (! $image) {
		return '';
	}

	$attachment_id = 0;
	$image_url     = '';
	$image_alt     = '';
	$width         = 0;
	$height        = 0;

	if (is_numeric($image)) {
		$attachment_id = (int) $image;
	} elseif (is_array($image)) {
		$attachment_id = (int) ($image['ID'] ?? ($image['id'] ?? 0));
		$image_url     = (string) ($image['url'] ?? '');
		$image_alt     = (string) ($image['alt'] ?? '');
		$width         = (int) ($image['width'] ?? 0);
		$height        = (int) ($image['height'] ?? 0);
	} elseif (is_string($image)) {
		$image_url = $image;
	}

	$attrs = array_merge(
		array(
			'loading'  => 'lazy',
			'decoding' => 'async',
		),
		$attrs
	);

	if (! isset($attrs['alt']) && $image_alt) {
		$attrs['alt'] = $image_alt;
	}

	if ($attachment_id) {
		return (string) wp_get_attachment_image($attachment_id, $size, false, $attrs);
	}

	if (! $image_url) {
		return '';
	}

	$attrs['alt'] = $attrs['alt'] ?? $image_alt;
	$attrs['src'] = $image_url;
	if ($width && ! isset($attrs['width'])) {
		$attrs['width'] = $width;
	}
	if ($height && ! isset($attrs['height'])) {
		$attrs['height'] = $height;
	}

	$attributes = '';
	foreach ($attrs as $name => $value) {
		if (null === $value || false === $value) {
			continue;
		}
		$attributes .= sprintf(' %s="%s"', esc_attr($name), esc_attr((string) $value));
	}

	return '<img' . $attributes . '>';
}

/**
 * Return the contextual page title.
 */
function theme_get_title(): string
{
	if (is_home() && get_option('page_for_posts')) {
		return get_the_title((int) get_option('page_for_posts'));
	}

	if (is_archive()) {
		return wp_strip_all_tags(get_the_archive_title());
	}

	if (is_search()) {
		return sprintf(
			/* translators: %s: search query. */
			__('Search results for: %s', 'base-wp-theme'),
			get_search_query(false)
		);
	}

	if (is_404()) {
		return __('Page not found', 'base-wp-theme');
	}

	return get_the_title();
}

/**
 * Return a navigation menu as a nested tree.
 *
 * @param string $location Registered theme menu location.
 * @return array<int, array{item: WP_Post, children: array}>
 */
function theme_get_menu_tree(string $location): array
{
	$locations = get_nav_menu_locations();

	if (empty($locations[$location])) {
		return array();
	}

	$items = wp_get_nav_menu_items($locations[$location]);

	if (! is_array($items)) {
		return array();
	}

	_wp_menu_item_classes_by_context($items);
	$items_by_parent = array();

	foreach ($items as $item) {
		$items_by_parent[(int) $item->menu_item_parent][] = $item;
	}

	$build_branch = static function (int $parent_id) use (&$build_branch, $items_by_parent): array {
		$branch = array();

		foreach ($items_by_parent[$parent_id] ?? array() as $item) {
			$branch[] = array(
				'item'     => $item,
				'children' => $build_branch((int) $item->ID),
			);
		}

		return $branch;
	};

	return $build_branch(0);
}
