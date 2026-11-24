<?php
if (is_home()) {
	// A latest-posts homepage should retain the normal archive and pagination.
	include get_theme_file_path('/index.php');
} else {
	get_template_part('templates/page', 'header');
	get_template_part('templates/content', 'page');
}
