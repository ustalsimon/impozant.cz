<?php while ( have_posts() ) : ?>
	<?php
	the_post();
	$intro = theme_get_field( 'impozant_page_intro', get_the_ID(), '' );
	$content = theme_get_field( 'impozant_page_content', get_the_ID(), '' );
	?>
	<?php if ( is_string( $intro ) && '' !== $intro ) : ?>
		<div class="page-intro"><?php echo wp_kses_post( $intro ); ?></div>
	<?php endif; ?>
	<?php if ( is_string( $content ) && '' !== $content ) : ?>
		<div class="page-content"><?php echo wp_kses_post( $content ); ?></div>
	<?php else : ?>
		<?php the_content(); ?>
	<?php endif; ?>
	<?php wp_link_pages( array( 'before' => '<nav class="pagination">', 'after' => '</nav>' ) ); ?>
<?php endwhile; ?>
