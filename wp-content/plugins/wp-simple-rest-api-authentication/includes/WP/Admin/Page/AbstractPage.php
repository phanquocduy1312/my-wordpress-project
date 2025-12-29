<?php

declare(strict_types=1);

namespace OneTeamSoftware\WP\Admin\Page;

abstract class AbstractPage implements PageInterface
{
	/**
	 * @var string
	 */
	protected $page;

	/**
	 * @var string
	 */
	protected $parentMenu;

	/**
	 * @var string
	 */
	protected $pageTitle;

	/**
	 * @var string
	 */
	protected $menuTitle;

	/**
	 * @var string
	 */
	protected $capability;

	/**
	 * constructor
	 *
	 * @param string $page
	 * @param string $parentMenu
	 * @param string $pageTitle
	 * @param string $menuTitle
	 * @param string $capability
	 */
	protected function __construct(
		string $page,
		string $parentMenu,
		string $pageTitle,
		string $menuTitle,
		string $capability = ''
	) {
		$this->page = $page;
		$this->parentMenu = $parentMenu;
		$this->pageTitle = $pageTitle;
		$this->menuTitle = $menuTitle;
		$this->capability = $capability;

		add_action('admin_menu', [$this, 'onAdminMenu']);
		add_filter('woocommerce_screen_ids', [$this, 'onScreenIds']);
	}

	/**
	 * Adds submenu under WooCommerce menu
	 *
	 * @return void
	 */
	public function onAdminMenu(): void
	{
		add_submenu_page(
			$this->parentMenu,
			$this->pageTitle,
			$this->menuTitle,
			$this->capability,
			$this->page,
			[$this, 'displayPage']
		);
	}

	/**
	 * Registers this page with woocommerce, so it will add required resources
	 *
	 * @param array $screenIds
	 * @return array
	 */
	public function onScreenIds(array $screenIds): array
	{
		// we need to register page with woocommerce_page prefix as it is its submenu so it will activate all the resources we need
		$screenIds[] = 'woocommerce_page_' . $this->page;

		return $screenIds;
	}

	/**
	 * Render the admin page
	 *
	 * @return void
	 */
	public function displayPage(): void
	{
		// Check the user capabilities
		if (!$this->canUserViewThisPage()) {
			wp_die('You do not have sufficient permissions to access this page.');
		}

		$this->displayHeader();
		$this->display();
		$this->displayFooter();
	}

	/**
	 * Renders admin page
	 *
	 * @return void
	 */
	abstract public function display(): void;

	/**
	 * Returns true when user can view this page
	 *
	 * @return boolean
	 */
	protected function canUserViewThisPage(): bool
	{
		if (!function_exists(('current_user_can'))) {
			include_once(ABSPATH . 'wp-includes/pluggable.php');
		}

		if (empty($this->capability) || current_user_can($this->capability)) {
			return true;
		}

		return false;
	}

	/**
	 * Render the admin plugin page header
	 *
	 * @return void
	 */
	protected function displayHeader(): void
	{
		echo '<div class="wrap woocommerce">';
	}

	/**
	 * Render the admin plugin page footer
	 *
	 * @return void
	 */
	protected function displayFooter(): void
	{
		echo '</div>';
	}
}
