<?php
/**
 * Theme bootstrap.
 */
if (! defined('ABSPATH')) {
	exit;
}

$theme_modules = array(
	'inc/helpers.php',
	'inc/setup.php',
	'inc/assets.php',
	'inc/scf.php',
	'inc/template-wrapper.php',
);

foreach ($theme_modules as $theme_module) {
	require_once get_theme_file_path('/' . $theme_module);
}
