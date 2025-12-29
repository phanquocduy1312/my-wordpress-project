<?php

declare(strict_types=1);

namespace OneTeamSoftware\WP\SimpleRestApiAuthentication;

use OneTeamSoftware\Mutex\FileMutex;
use OneTeamSoftware\WC\Admin\LogExporter\LogExporter;
use OneTeamSoftware\WC\Logger\Logger;
use OneTeamSoftware\WP\PluginDependency\PluginDependency;
use OneTeamSoftware\WP\SettingsStorage\SettingsStorage;
use OneTeamSoftware\WP\SimpleRestApiAuthentication\Admin\SettingsPage;

class Plugin
{
	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var string
	 */
	private $pluginPath;

	/**
	 * @var string
	 */
	private $title;

	/**
	 * @var string
	 */
	private $description;

	/**
	 * @var string
	 */
	private $version;

	/**
	 * @var PluginDependency
	 */
	private $pluginDependency;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var LogExporter
	 */
	private $logExporter;

	/**
	 * @var SettingsStorage
	 */
	private $settingsStorage;

	/**
	 * @var AuthenticationService
	 */
	private $authenticatonService;

	/**
	 * @var array
	 */
	private $defaultSettings;

	/**
	 * constructor
	 *
	 * @param string $pluginPath
	 * @param string $title
	 * @param string $description
	 * @param string $version
	 */
	public function __construct(
		string $pluginPath,
		string $title = '',
		string $description = '',
		string $version = null
	) {
		$this->id = preg_replace('/-pro$/', '', basename($pluginPath, '.php'));
		$this->pluginPath = $pluginPath;
		$this->title = $title;
		$this->description = $description;
		$this->version = $version;
		$this->pluginDependency = new PluginDependency($this->id, $this->title);
		$this->logger = new Logger($this->id);
		$this->logExporter = new LogExporter($this->id, get_class($this));
		$this->settingsStorage = new SettingsStorage($this->id, new FileMutex($this->id));
		$this->authenticatonService = new AuthenticationService($this->logger);

		$this->defaultSettings = [
			'debug' => false,
			'requireSSL' => true,
		];
	}

	/**
	 * registers plugin
	 *
	 * @return void
	 */
	public function register(): void
	{
		if (false === $this->canRegister()) {
			return;
		}

		add_action('plugins_loaded', [$this, 'onPluginsLoaded'], PHP_INT_MAX, 0);
		add_filter($this->id . '_settingsstorage_get', [$this, 'addDefaultSettings'], 1, 1);

		$this->setPluginSettings($this->settingsStorage->get());
		$this->logExporter->register();
		$this->authenticatonService->register();
	}

	/**
	 * adds link to settings page
	 *
	 * @param array $links
	 * @return array
	 */
	public function onPluginActionLinks(array $links): array
	{
		$link = sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=' . $this->id), __('Settings', $this->id));
		array_unshift($links, $link);
		return $links;
	}

	/**
	 * handles plugins loaded hook
	 *
	 * @return void
	 */
	public function onPluginsLoaded(): void
	{
		$this->initAdminFeatures();
	}

	/**
	 * adds default settings to the given settings
	 *
	 * @param array $settings
	 * @return array
	 */
	public function addDefaultSettings(array $settings): array
	{
		return array_merge($this->defaultSettings, $settings);
	}

	/**
	 * sets plugin settings
	 *
	 * @param array $settings
	 * @return void
	 */
	private function setPluginSettings(array $settings): void
	{
		$this->authenticatonService->withRequireSSL(filter_var($settings['requireSSL'] ?? false, FILTER_VALIDATE_BOOLEAN));
		$this->logger->setEnabled(filter_var($settings['debug'] ?? false, FILTER_VALIDATE_BOOLEAN));
	}

	/**
	 * initializes admin features
	 *
	 * @return void
	 */
	private function initAdminFeatures(): void
	{
		if (false === is_admin()) {
			return;
		}

		add_filter('plugin_action_links_' . plugin_basename($this->pluginPath), [$this, 'onPluginActionLinks'], 1, 1);

		$this->initSettingsPage();
	}

	/**
	 * initializes settings page
	 *
	 * @return void
	 */
	private function initSettingsPage(): void
	{
		(new SettingsPage(
			$this->id,
			$this->title,
			$this->description,
			$this->pluginPath,
			$this->version,
			$this->logExporter,
			$this->settingsStorage,
		))->register();
	}

	/**
	 * returns true when plugin can register
	 *
	 * @return bool
	 */
	private function canRegister(): bool
	{
		$this->pluginDependency
			->register()
			->add(
				'woocommerce/woocommerce.php',
				__('WooCommerce', $this->id),
				admin_url('/plugin-install.php?tab=plugin-information&plugin=woocommerce&TB_iframe=true&width=600&height=550')
			);

		if (false === $this->pluginDependency->validate()) {
			return false;
		}

		return true;
	}
}
