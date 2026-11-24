<time class="published" datetime="<?php echo esc_attr(get_the_time('c')); ?>"><?php echo esc_html(get_the_date()); ?></time>
<p class="byline author vcard">
	<?php esc_html_e('By', 'base-wp-theme'); ?>
	<a href="<?php echo esc_url(get_author_posts_url((int) get_the_author_meta('ID'))); ?>" rel="author" class="fn"><?php the_author(); ?></a>
</p>