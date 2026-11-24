<?php while ( have_posts() ) : ?>
	<?php the_post(); ?>
	<article <?php post_class(); ?>>
		<header>
			<h1 class="entry-title"><?php the_title(); ?></h1>
			<?php get_template_part( 'templates/entry-meta' ); ?>
		</header>
		<div class="entry-content"><?php the_content(); ?></div>
		<?php wp_link_pages( array( 'before' => '<nav class="page-nav">', 'after' => '</nav>' ) ); ?>
	</article>
	<?php if (comments_open() || get_comments_number()) { comments_template('/templates/comments.php'); } ?>
<?php endwhile; ?>
