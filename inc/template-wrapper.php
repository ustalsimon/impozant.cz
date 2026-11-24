<?php

/**
 * Legacy Roots-style template wrapper retained for layout compatibility.
 *
 * @package Base WP Theme
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Return the template selected by WordPress before it is wrapped by base.php.
 */
function theme_get_template_path(): string
{
	return Theme_Template_Wrapper::$main_template;
}

/**
 * Resolve the base layout around the selected WordPress template.
 */
class Theme_Template_Wrapper
{
	public static string $main_template = '';
	public static $base = false;

	/** @var array<int, string> */
	private array $templates;

	public function __construct(string $template = 'base.php')
	{
		$this->templates = array($template);

		if (self::$base) {
			$stem = substr($template, 0, -4);
			array_unshift($this->templates, sprintf($stem . '-%s.php', self::$base));
		}
	}

	public function __toString(): string
	{
		$templates = apply_filters(
			'theme_wrap_' . basename($this->templates[0], '.php'),
			$this->templates
		);

		return (string) locate_template($templates);
	}

	public static function wrap(string $main): Theme_Template_Wrapper
	{
		self::$main_template = $main;
		self::$base          = basename(self::$main_template, '.php');

		if ('index' === self::$base) {
			self::$base = false;
		}

		return new self();
	}
}
add_filter('template_include', array('Theme_Template_Wrapper', 'wrap'), 99);
