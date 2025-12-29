<?php

declare(strict_types=1);

namespace OneTeamSoftware\WP\Admin\Page;

abstract class AbstractPageTab implements PageInterface
{
	/**
	 * @var string
	 */
	protected $tabId;

	/**
	 * @var string
	 */
	protected $capability;

	/**
	 * Constructor
	 *
	 * @param string $tabId
	 * @param string $capability
	 */
	public function __construct(string $tabId, string $capability = '')
	{
		$this->tabId = $tabId;
		$this->capability = $capability;
	}

	/**
	 * Returns tab id
	 *
	 * @return string
	 */
	public function getTabId(): string
	{
		return $this->tabId;
	}

	/**
	 * Returns tab title text
	 *
	 * @return string
	 */
	abstract public function getTabTitle(): string;

	/**
	 * Displays contents of the tab
	 *
	 * @return void
	 */
	public function display(): void
	{
		if (!empty($this->capability) && !current_user_can($this->capability)) {
			wp_die('You do not have sufficient permissions to access this page.');
		}

		echo '<div class="wrap">';
		$this->displayHeader();
		$this->displayContents();
		echo '</div>';
	}

	/**
	 * Returns tab header title
	 *
	 * @return string
	 */
	protected function getHeaderTitle(): string
	{
		return '';
	}

	/**
	 * Displays contents of the tab
	 *
	 * @return void
	 */
	abstract protected function displayContents(): void;

	/**
	 * Returns array of header buttons
	 *
	 *  Format:
	 *  array(
	 * 		array(
	 * 			'title' => 'Button #1',
	 * 			'url' => 'someurl'
	 * 		)
	 *  )
	 *
	 * @return array
	 */
	protected function getHeaderButtons(): array
	{
		return [];
	}

	/**
	 * Display table header in this method
	 *
	 * @return void
	 */
	protected function displayHeader(): void
	{
		$title = $this->getHeaderTitle();
		if (!empty($title)) {
			echo '<h1 class="wp-heading-inline">' . esc_html($title) . '</h1>';
		}

		$buttons = $this->getHeaderButtons();
		if (!empty($buttons) && is_array($buttons)) {
			foreach ($buttons as $button) {
				echo $this->buildButtonHtml($button);
			}
		}

		echo '<hr class="wp-header-end">';
	}

	/**
	 * builds button's HTML
	 *
	 * @param array $button
	 * @return string
	 */
	protected function buildButtonHtml(array $button): string
	{
		if (empty($button['url']) || empty($button['title'])) {
			return '';
		}

		$customAttributes = $button['custom_attributes'] ?? [];
		$customAttributes['class'] = ($customAttributes['class'] ?? '') . ' page-title-action';

		return sprintf(
			'<a href="%s" %s>%s</a>',
			esc_attr($button['url']),
			$this->buildCustomAttributesHtml($customAttributes),
			esc_html($button['title'])
		);
	}

	/**
	 * builds custom attributes HTML
	 *
	 * @param array $customAttributes
	 * @return string
	 */
	protected function buildCustomAttributesHtml(array $customAttributes): string
	{
		if (empty($customAttributes)) {
			return '';
		}

		$html = '';
		foreach ($customAttributes as $attribute => $value) {
			$html .= sprintf('%s="%s" ', esc_attr($attribute), esc_attr($value));
		}

		return trim($html);
	}
}
