<?php get_template_part( 'templates/head' ); ?>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<?php do_action( 'get_header' ); ?>
	<?php get_template_part( 'templates/header' ); ?>

	<div class="wrap" role="document">
		<div class="content">
			<main id="main-content" class="main" role="main">
				<?php include theme_get_template_path(); ?>
			</main>
		</div>
	</div>

	<?php get_template_part( 'templates/footer' ); ?>
</body>
</html>
