<?php

declare(strict_types=1);

namespace OneTeamSoftware\WP\Admin\Page;

class PageTabs
{
	/**
	 * @var string
	 */
	protected $page;

	/**
	 * @var array
	 */
	protected $tabs;

	/**
	 * constructor
	 *
	 * @param string $page
	 */
	public function __construct(string $page)
	{
		$this->page = $page;
		$this->tabs = [];
	}

	/**
	 * Register new tab
	 *
	 * @param AbstractPageTab $tab
	 * @return void
	 */
	public function addTab(AbstractPageTab $tab): void
	{
		$this->tabs[$tab->getTabId()] = $tab;
	}

	/**
	 * Renders tabs
	 *
	 * @return void
	 */
	public function display(): void
	{
		if (empty($this->tabs) || empty($_GET['page']) || $_GET['page'] !== $this->page) {
			return;
		}

		$currentTabId = '';
		if (isset($_GET['tab']) && isset($this->tabs[$_GET['tab']])) {
			$currentTabId = sanitize_key($_GET['tab']);
		} else {
			$currentTabId = key($this->tabs);
		}

		$this->displayTabs($currentTabId);
		$this->displayTabContents($currentTabId);
	}

	/**
	 * Render the admin plugin page tabs
	 *
	 * @param string $currentTabId
	 * @return void
	 */
	protected function displayTabs(string $currentTabId): void
	{
		echo '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">';
		foreach ($this->tabs as $tabId => $tab) {
			$text = $tab->getTabTitle();
			$url = admin_url('admin.php?page=' . $this->page . '&tab=' . $tabId);

			echo '<a href="' . esc_url($url) . '" class="nav-tab ' . ($currentTabId === $tabId ? 'nav-tab-active' : '') . '">' .
				esc_html($text) .
				'</a>';
		}
		echo '</h2>';
	}

	/**
	 * Render contents of a current admin page tab
	 *
	 * @param string $currentTabId
	 * @return void
	 */
	protected function displayTabContents(string $currentTabId): void
	{
		if (isset($this->tabs[$currentTabId])) {
			$this->tabs[$currentTabId]->display();
		}
	}
}
