<?php

/**
 * Secure Custom Fields integration.
 *
 * Local JSON stores field-group definitions only. Content values remain in the
 * WordPress database.
 *
 * @package Base WP Theme
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Save SCF field-group definitions in the theme.
 */
function theme_scf_save_json_path(): string
{
	return get_theme_file_path('/scf-json');
}
add_filter('acf/settings/save_json', 'theme_scf_save_json_path');

/**
 * Add the theme field-group definitions to SCF's lookup paths.
 *
 * @param array<int, string> $paths Existing JSON lookup paths.
 * @return array<int, string>
 */
function theme_scf_load_json_paths(array $paths): array
{
	$custom_path = get_theme_file_path('/scf-json');

	if (! in_array($custom_path, $paths, true)) {
		$paths[] = $custom_path;
	}

	return $paths;
}
add_filter('acf/settings/load_json', 'theme_scf_load_json_paths');
