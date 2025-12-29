<?php

declare(strict_types=1);

namespace OneTeamSoftware\WP\PluginDependency;

class PluginDependency
{
	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var array
	 */
	private $plugins;

	/**
	 * @var array
	 */
	private $missingPlugins;

	/**
	 * constructor
	 *
	 * @param string $id
	 * @param string $name
	 */
	public function __construct(string $id, string $name)
	{
		$this->id = $id;
		$this->name = $name;
		$this->plugins = [];
		$this->missingPlugins = [];
	}

	/**
	 * register the plugin dependency
	 *
	 * @return PluginDependency
	 */
	public function register(): PluginDependency
	{
		add_action('admin_notices', [$this, 'displayNotice']);

		return $this;
	}

	/**
	 * adds a plugin to the list of required plugins
	 *
	 * @param string $file
	 * @param string $name
	 * @param string $url
	 * @return PluginDependency
	 */
	public function add(string $file, string $name, string $url): PluginDependency
	{
		$this->missingPlugins = [];
		$this->plugins[$file] = [$name, $url];

		return $this;
	}

	/**
	 * validate the list of plugins
	 *
	 * @return bool
	 */
	public function validate(): bool
	{
		if (!function_exists('is_plugin_active')) {
			require_once(ABSPATH . '/wp-admin/includes/plugin.php');
		}

		if (empty($this->missingPlugins)) {
			foreach ($this->plugins as $file => $plugin) {
				if (!is_plugin_active($file)) {
					$this->missingPlugins[$file] = $plugin;
				}
			}
		}

		return empty($this->missingPlugins);
	}

	/**
	 * Display notice
	 *
	 * @return void
	 */
	public function displayNotice(): void
	{
		if ($this->validate()) {
			return;
		}

		$notice = sprintf(
			'<div id="message" class="error"><p><strong>%s</strong> %s</p>',
			esc_html($this->name),
			__('requires the following plugins to be installed and activated:', $this->id)
		);

		foreach ($this->missingPlugins as $plugin) {
			$notice .= sprintf(
				'<li><a href="%s" class="thickbox open-plugin-details-modal" target="_blank">%s</a></li>',
				esc_attr($plugin[1]),
				esc_html($plugin[0])
			);
		}

		$notice .= '<p></p></div>';

		echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped - notice is already escaped
	}
}
