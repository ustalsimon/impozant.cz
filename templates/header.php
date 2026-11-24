<header class="site-header">
 <div class="site-container header-inner">
  <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Impozant — domů"><img src="<?php echo esc_url(get_theme_file_uri('/assets/images/home/logo.png')); ?>" alt="Impozant"></a>
  <nav class="main-nav" aria-label="Hlavní navigace">
   <a href="<?php echo esc_url(home_url('/')); ?>">Domů</a><a href="<?php echo esc_url(home_url('/obchod/')); ?>">Obchod</a><a href="<?php echo esc_url(home_url('/inspirace/')); ?>">Inspirace</a><a href="<?php echo esc_url(home_url('/#o-nas')); ?>">O nás</a><a href="<?php echo esc_url(home_url('/#kontakt')); ?>">Kontakt</a>
  </nav>
  <div class="header-actions"><a href="<?php echo esc_url(home_url('/?s=')); ?>" aria-label="Vyhledat"><i class="fas fa-search" aria-hidden="true"></i></a><a href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/kosik/')); ?>" aria-label="Košík"><i class="fas fa-shopping-basket" aria-hidden="true"></i></a></div>
 </div>
</header>
