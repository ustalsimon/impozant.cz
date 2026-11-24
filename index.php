<?php

/**
 * Fallback template required by classic WordPress themes.
 *
 * @package Base WP Theme
 */

if (have_posts()) :
	while (have_posts()) :
		the_post();
		get_template_part('templates/content', get_post_format());
	endwhile;

	the_posts_navigation();
else :
?>
	<p><?php esc_html_e('No content was found.', 'base-wp-theme'); ?></p>
<?php
endif;
