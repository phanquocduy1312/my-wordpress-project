<?php

declare(strict_types=1);

namespace OneTeamSoftware\WP\SimpleRestApiAuthentication\Admin;

use OneTeamSoftware\WC\Admin\LogExporter\LogExporter;
use OneTeamSoftware\WP\Admin\OneTeamSoftware;
use OneTeamSoftware\WP\Admin\Page\AbstractPage;
use OneTeamSoftware\WP\SettingsStorage\SettingsStorage;

class SettingsPage extends AbstractPage
{
	/**
	 * @var string
	 */
	private $id;

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
	private $pluginPath;

	/**
	 * @var string
	 */
	private $version;

	/**
	 * @var LogExporter
	 */
	private $logExporter;

	/**
	 * @var SettingsStorage
	 */
	private $settingsStorage;

	/**
	 * @var GeneralForm
	 */
	private $generalForm;

	/**
	 * constructor
	 *
	 * @param string $id
	 * @param string $title
	 * @param string $description
	 * @param string $pluginPath
	 * @param string $version
	 * @param LogExporter $logExporter
	 * @param SettingsStorage $settingsStorage
	 */
	public function __construct(
		string $id,
		string $title,
		string $description,
		string $pluginPath,
		string $version,
		LogExporter $logExporter,
		SettingsStorage $settingsStorage
	) {
		parent::__construct($id, 'oneteamsoftware', $title, $title, 'manage_options');

		$this->id = $id;
		$this->title = $title;
		$this->description = $description;
		$this->pluginPath = $pluginPath;
		$this->version = $version;
		$this->logExporter = $logExporter;
		$this->settingsStorage = $settingsStorage;
		$this->generalForm = $this->getGeneralForm();
	}

	/**
	 * registers settings page
	 *
	 * @return void
	 */
	public function register(): void
	{
		OneTeamSoftware::instance()->register();
	}

	/**
	 * displays page
	 *
	 * @return void
	 */
	public function display(): void
	{
		$this->enqueueScripts();

		echo sprintf('<h1 class="wp-heading-inline">%s</h1>', $this->title);

		$this->generalForm->display();
	}

	/**
	 * includes scripts
	 *
	 * @return void
	 */
	public function enqueueScripts(): void
	{
		$cssExt = defined('WP_DEBUG') && WP_DEBUG ? 'css' : 'min.css' ;
		$jsExt = defined('WP_DEBUG') && WP_DEBUG ? 'js' : 'min.js' ;

		wp_register_style(
			$this->id . '-SettingsPage',
			plugins_url('assets/css/SettingsPage.' . $cssExt, str_replace('phar://', '', $this->pluginPath)),
			['wp-jquery-ui-dialog'],
			$this->version
		);
		wp_enqueue_style($this->id . '-SettingsPage');

		wp_register_style(
			$this->id . '-switchify',
			plugins_url('assets/css/switchify.' . $cssExt, str_replace('phar://', '', $this->pluginPath)),
			[],
			$this->version
		);
		wp_enqueue_style($this->id . '-switchify');

		wp_register_script(
			$this->id . '-switchify',
			plugins_url('assets/js/switchify.' . $jsExt, str_replace('phar://', '', $this->pluginPath)),
			['jquery'],
			$this->version
		);
		wp_enqueue_script($this->id . '-switchify');
	}

	/**
	 * returns general form
	 *
	 * @return GeneralForm
	 */
	private function getGeneralForm(): GeneralForm
	{
		return new GeneralForm($this->id, $this->description, $this->logExporter, $this->settingsStorage);
	}
}
