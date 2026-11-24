<?php get_template_part( 'templates/page', 'header' ); ?>
<?php if ( have_posts() ) : ?>
	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>
		<?php get_template_part( 'templates/content', get_post_format() ); ?>
	<?php endwhile; ?>
	<?php the_posts_navigation(); ?>
<?php endif; ?>
