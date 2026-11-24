<?php
if (is_home() && ! is_front_page()) { include get_theme_file_path('/index.php'); return; }
$id = (int) get_queried_object_id();
$field = static function ($name, $default) use ($id) { return theme_get_field($name, $id, $default); };
?>
<?php
$slides = array(
 array('title' => $field('hero_title', 'Vinotéka | Impact'), 'text' => $field('hero_text', 'Nástěnná vinotéka se stylovým designem dodá vašemu bydlení moderní šarm. Upoutá pozornost každé vaší návštěvy.'), 'image' => $field('hero_image', get_theme_file_uri('/assets/images/home/hero.jpg')), 'link' => $field('hero_link', home_url('/produkt/vinoteka-impact/'))),
 array('title' => $field('slide_brunch_title', 'Podnos | Brunch'), 'text' => $field('slide_brunch_text', 'Tyto designové podnosy jsou vhodné k servírování, neboť obsahují výřez, na kterém si můžete vychutnat svůj oblíbený pokrm, snídani či zákusek.'), 'image' => $field('slide_brunch_image', get_theme_file_uri('/assets/images/home/brunch.jpg')), 'link' => $field('slide_brunch_link', home_url('/produkt/podnos-brunch/'))),
 array('title' => $field('slide_sun_title', 'Podnos | Sun'), 'text' => $field('slide_sun_text', 'Moderní masivní kulaté prkénko z dubového dřeva, které zdobí po stranách kožená madla, se pyšní průměrem 35 cm a tloušťkou 2 cm.'), 'image' => $field('slide_sun_image', get_theme_file_uri('/assets/images/home/sun.jpg')), 'link' => $field('slide_sun_link', home_url('/produkt/prkenko-sun/'))),
 array('title' => $field('slide_arch_title', 'Váza | Arch'), 'text' => $field('slide_arch_text', 'Krásně tvarovaná váza s matnou strukturou povrchu v béžové barvě. Využití najde na květiny, ať už řezané nebo sušené, ale také jako dominantní dekorace sama o sobě.'), 'image' => $field('slide_arch_image', get_theme_file_uri('/assets/images/home/arch.jpg')), 'link' => $field('slide_arch_link', home_url('/produkt/vaza-arch/'))),
);
?>
<section class="hero-slider site-container" aria-label="Nejoblíbenější produkty">
 <?php foreach ($slides as $index => $slide) : ?>
 <div class="hero" data-slide="<?php echo esc_attr((string) $index); ?>" <?php echo $index ? 'hidden' : ''; ?>>
  <div class="hero-copy"><span class="eyebrow"><?php echo esc_html($field('hero_label', 'Nejoblíbenější produkt')); ?></span><h1><?php echo esc_html($slide['title']); ?></h1><p><?php echo esc_html($slide['text']); ?></p><a class="button" href="<?php echo esc_url($slide['link']); ?>">Více zde</a></div>
  <img class="hero-image" src="<?php echo esc_url($slide['image']); ?>" alt="<?php echo esc_attr($slide['title']); ?>">
 </div>
 <?php endforeach; ?>
 <div class="slider-dots" role="group" aria-label="Vyberte produkt"><?php foreach ($slides as $index => $slide) : ?><button type="button" data-slide-index="<?php echo esc_attr((string) $index); ?>" aria-label="<?php echo esc_attr($slide['title']); ?>" aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>"></button><?php endforeach; ?></div>
</section>
<script>document.querySelectorAll('.hero-slider').forEach(function(slider){slider.querySelectorAll('[data-slide-index]').forEach(function(button){button.addEventListener('click',function(){var n=button.dataset.slideIndex;slider.querySelectorAll('[data-slide]').forEach(function(slide){slide.hidden=slide.dataset.slide!==n});slider.querySelectorAll('[data-slide-index]').forEach(function(dot){dot.setAttribute('aria-pressed',String(dot===button))})})})});</script>
<section id="o-nas" class="site-container split"><img src="<?php echo esc_url($field('about_image', get_theme_file_uri('/assets/images/home/about.jpg'))); ?>" alt=""><div class="split-copy"><h2><?php echo esc_html($field('about_title', 'O NÁS')); ?></h2><div><?php echo wp_kses_post($field('about_text', '<p>MÁTE RÁDI PŘÍRODNÍ MATERIÁL? DŘEVO, KŮŽI?<br>RÁDI ZÚTULŇUJETE SVÉ DOMOVY?<br>PAK JSTE TADY SPRÁVNĚ.</p><p>TO VŠE JE IMPOZANT.</p><p>NAŠE FIRMA VYTVÁŘÍ DOKONALOU HARMONII SPOJENÍM TĚCHTO MATERIÁLŮ S VELKÝM DESIGNOVÝM CITEM PRO DETAIL.<br>TO VŠE JAKO CELEK DOPLŇUJE VÁŠ DOMOV.</p>')); ?></div></div></section>
<section class="site-container split story"><div class="split-copy"><h2><?php echo esc_html($field('story_title', 'A KDO STOJÍ ZA FIRMOU IMPOZANT?')); ?></h2><div><?php echo wp_kses_post($field('story_text', '<p>NA POZADÍ ZNAČKY IMPOZANT STOJÍ MAMINKA POCHÁZEJÍCÍ Z PODHŮŘÍ BESKYD, KTERÁ MILUJE PŘÍRODU, DESIGN A TEPLO DOMOVA. ROZHODLA SE TYTO VĚCI SPOJIT DOHROMADY A VYTVOŘIT LOVEBRAND, A TÍM JE IMPOZANT.</p><p>IVANA</p>')); ?></div></div><img src="<?php echo esc_url($field('story_image', get_theme_file_uri('/assets/images/home/story.jpg'))); ?>" alt=""></section>
<section class="benefits"><div class="site-container benefits-inner"><div><span class="fas fa-check-circle" aria-hidden="true"></span>Kvalita na prvním místě</div><div><span class="fas fa-map-marker-alt" aria-hidden="true"></span>Česká výroba</div><div><span class="fas fa-heart" aria-hidden="true"></span>Vyrobeno s láskou</div></div></section>
<section class="site-container shop-grid" aria-label="Obchod">
 <?php for ($i = 1; $i <= 4; $i++) : ?><img src="<?php echo esc_url(get_theme_file_uri('/assets/images/home/gallery'.$i.'.jpg')); ?>" alt=""><?php endfor; ?>
 <div class="shop-grid-callout"><h2>OBCHOD</h2><a class="button" href="<?php echo esc_url(home_url('/obchod/')); ?>">Kliknutím přejdete do obchodu</a></div>
 <?php for ($i = 5; $i <= 8; $i++) : ?><img src="<?php echo esc_url(get_theme_file_uri('/assets/images/home/gallery'.$i.'.jpg')); ?>" alt=""><?php endfor; ?>
</section>
<section id="kontakt" class="contacts"><div class="site-container contact-grid"><div><i class="fas fa-phone" aria-hidden="true"></i><div><h3>Telefonní číslo</h3><a href="tel:+420704831905">+420 734 384 465</a></div></div><div><i class="fas fa-envelope" aria-hidden="true"></i><div><h3>Email</h3><a href="mailto:impozant@email.cz">impozant@email.cz</a></div></div><div><i class="fab fa-instagram" aria-hidden="true"></i><div><h3>Sociální sítě</h3><a href="https://www.instagram.com/impozant.cz/">impozant.cz</a></div></div></div></section>
