<?php

declare(strict_types=1);

namespace OneTeamSoftware\WP\Admin\Page;

class PageTab extends AbstractPageTab
{
	/**
	 * @var string
	 */
	private $tabTitle;

	/**
	 * @var PageInterface
	 */
	private $page;

	/**
	 * @var string
	 */
	private $headerTitle;

	/**
	 * @var array
	 */
	private $headerButtons;

	/**
	 * Constructor
	 *
	 * @param string $tabId
	 * @param string $capability
	 * @param string $tabTitle
	 * @param PageInterface $page
	 * @param string $headerTitle
	 * @param array $headerButtons
	 */
	public function __construct(
		string $tabId,
		string $capability,
		string $tabTitle,
		PageInterface $page,
		string $headerTitle = '',
		array $headerButtons = []
	) {
		parent::__construct($tabId, $capability);

		$this->tabTitle = $tabTitle;
		$this->page = $page;
		$this->headerTitle = $headerTitle;
		$this->headerButtons = $headerButtons;
	}

	/**
	 * returns tab tabTitle text
	 *
	 * @return string
	 */
	public function getTabTitle(): string
	{
		return $this->tabTitle;
	}

	/**
	 * returns tab header tabTitle
	 *
	 * @return string
	 */
	protected function getHeaderTitle(): string
	{
		return $this->headerTitle;
	}

	/**
	 * Returns array of header buttons
	 *
	 *  Format:
	 *  array(
	 *         array(
	 *             'title' => 'Button #1',
	 *             'url' => 'someurl'
	 *         )
	 *  )
	 *
	 * @return array
	 */
	protected function getHeaderButtons(): array
	{
		return $this->headerButtons;
	}

	/**
	 * displays contents of the tab
	 *
	 * @return void
	 */
	protected function displayContents(): void
	{
		$this->page->display();
	}
}
