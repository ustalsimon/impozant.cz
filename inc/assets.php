<?php
/** Manifest-based Vite assets; shared by watched and production builds. */
if (! defined('ABSPATH')) {
    exit;
}

function theme_enqueue_assets(): void
{
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }

    $manifest_path = get_theme_file_path('/assets/dist/manifest.json');
    if (! is_readable($manifest_path)) {
        return; // Build before activating or distributing the theme.
    }
    $manifest = json_decode((string) file_get_contents($manifest_path), true);
    $entry_key = 'assets/src/js/app.js';
    if (! is_array($manifest) || empty($manifest[$entry_key]['file'])) {
        return;
    }

    // Imported chunks may own CSS. Follow static imports once, avoiding cycles.
    $seen = array();
    $styles = array();
    $collect = static function (string $key) use (&$collect, &$seen, &$styles, $manifest): void {
        if (isset($seen[$key]) || ! isset($manifest[$key])) {
            return;
        }
        $seen[$key] = true;
        foreach ($manifest[$key]['css'] ?? array() as $css) {
            $styles[$css] = true;
        }
        foreach ($manifest[$key]['imports'] ?? array() as $import) {
            $collect($import);
        }
    };
    $collect($entry_key);
    foreach (array_keys($styles) as $css) {
        $path = get_theme_file_path('/assets/dist/' . $css);
        $version = is_file($path) ? (string) filemtime($path) : null;
        wp_enqueue_style('theme-' . md5($css), get_theme_file_uri('/assets/dist/' . $css), array(), $version);
    }
    wp_enqueue_script('theme-main', get_theme_file_uri('/assets/dist/' . $manifest[$entry_key]['file']), array(), null, true);
}
add_action('wp_enqueue_scripts', 'theme_enqueue_assets', 100);

/** Vite emits ES modules. Keep the filter scoped to the theme entry. */
function theme_module_script_tag(string $tag, string $handle, string $src): string
{
    if ('theme-main' !== $handle) {
        return $tag;
    }
    return '<script type="module" src="' . esc_url($src) . '" id="theme-main-js"></script>' . "\n";
}
add_filter('script_loader_tag', 'theme_module_script_tag', 10, 3);

/** Project styles also work when a Vite build is unavailable. */
function impozant_enqueue_styles(): void
{
    wp_enqueue_style('impozant-style', get_stylesheet_uri(), array(), (string) wp_get_theme()->get('Version'));
}
add_action('wp_enqueue_scripts', 'impozant_enqueue_styles', 110);

function impozant_enqueue_icons(): void
{
    wp_enqueue_style('impozant-fontawesome', get_theme_file_uri('/assets/fonts/fontawesome/css/fontawesome.min.css'), array(), '5.15.3');
    wp_enqueue_style('impozant-fontawesome-solid', get_theme_file_uri('/assets/fonts/fontawesome/css/solid.min.css'), array('impozant-fontawesome'), '5.15.3');
    wp_enqueue_style('impozant-fontawesome-brands', get_theme_file_uri('/assets/fonts/fontawesome/css/brands.min.css'), array('impozant-fontawesome'), '5.15.3');
}
add_action('wp_enqueue_scripts', 'impozant_enqueue_icons');
