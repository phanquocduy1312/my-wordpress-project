<?php

declare(strict_types=1);

namespace OneTeamSoftware\WP\Admin\Notices;

class Notices
{
	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var bool
	 */
	protected $displayWithoutNotices;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var int
	 */
	protected $delay;

	/**
	 * @var bool
	 */
	protected $permanent;

	/**
	 * @var bool
	 */
	protected $dismissible;

	/**
	 * @var int
	 */
	protected $createdAt;

	/**
	 * @var bool
	 */
	protected $dismissed;

	/**
	 * @var array
	 */
	protected $notices;

	/**
	 * @var string
	 */
	protected $dismissAction;

	/**
	 * @var bool
	 */
	protected $loaded;

	/**
	 * constructor
	 *
	 * @param string $id
	 * @param array $properties
	 */
	public function __construct(string $id = 'admin_notices', array $properties = [])
	{
		$this->id = $id;
		$this->displayWithoutNotices = false;
		$this->type = 'error';
		$this->title = '';
		$this->delay = 0;
		$this->permanent = false;
		$this->dismissible = false;
		$this->createdAt = time();
		$this->dismissed = false;
		$this->notices = [];
		$this->dismissAction = $this->id . '_dismiss';
		$this->loaded = false;
		$this->setProperties($properties);

		add_action('wp_ajax_' . $this->dismissAction, [$this, 'dismiss']);
		add_action('admin_notices', [$this, 'display']);
		add_action('shutdown', [$this, 'save']);
		add_filter('wp_redirect', [$this, 'onRedirect'], 100, 2);
	}

	/**
	 * returns requested property
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function __get(string $property)
	{
		if (property_exists($this, $property)) {
			return $this->$property;
		}

		return null;
	}

	/**
	 * sets value of a requested property
	 *
	 * @param string $property
	 * @param mixed $value
	 * @return void
	 */
	public function __set(string $property, $value): void
	{
		if (property_exists($this, $property)) {
			if (is_numeric($this->property)) {
				$this->$property = floatval($value);
			} elseif (is_bool($this->property)) {
				$this->$property = filter_var($value, FILTER_VALIDATE_BOOLEAN);
			} else {
				$this->$property = $value;
			}
		}
	}

	/**
	 * dismisses notice
	 *
	 * @return void
	 */
	public function dismiss(): void
	{
		if (wp_verify_nonce(sanitize_key($_REQUEST['nonce']), $this->dismissAction)) {
			$this->load();
			$this->dismissed = true;
			wp_send_json_success();
		}
	}

	/**
	 * adds a notice
	 *
	 * @param string $notice
	 * @param string $noticeKey
	 * @return void
	 */
	public function add(string $notice, string $noticeKey = null): void
	{
		if (empty($notice)) {
			return;
		}

		if (empty($noticeKey)) {
			$noticeKey = md5($notice);
		}

		$this->notices[$noticeKey] = $notice;
	}

	/**
	 * displays notice
	 *
	 * @return void
	 */
	public function display(): void
	{
		$this->load();

		if (!$this->canDisplay()) {
			return;
		}

		$this->displayNotices();

		if (!$this->permanent) {
			$this->notices = [];
			$this->displayWithoutNotices = false;
		}
	}

	/**
	 * handles redirect
	 *
	 * @param string $location
	 * @param mixed $status
	 * @return string
	 */
	public function onRedirect(string $location, $status): string
	{
		$this->save();

		return $location;
	}

	/**
	 * saves notice
	 *
	 * @return void
	 */
	public function save(): void
	{
		if (!$this->loaded && empty($this->notices) && !$this->displayWithoutNotices) {
			return;
		}

		$cacheKey = $this->getCacheKey();
		delete_transient($cacheKey);

		if (false === empty($this->notices) || $this->displayWithoutNotices) {
			set_transient($cacheKey, get_object_vars($this));

			// extra effort to save dismissed to work around cache
			if ($this->dismissed) {
				update_user_meta(get_current_user_id(), $cacheKey . '_dismissed', true);
			}
		}
	}

	/**
	 * sets properties
	 *
	 * @param array $properties
	 * @return void
	 */
	private function setProperties(array $properties): void
	{
		foreach ($properties as $property => $value) {
			$this->__set($property, $value);
		}
	}

	/**
	 * loads notices
	 *
	 * @return void
	 */
	private function load(): void
	{
		if ($this->loaded) {
			return;
		}

		$cacheKey = $this->getCacheKey();
		$properties = get_transient($cacheKey, null);
		if (is_array($properties)) {
			$this->setProperties($properties);
		}

		if (empty($this->dismissed) && get_user_meta(get_current_user_id(), $cacheKey . '_dismissed', true)) {
			$this->dismissed = true;
		}

		$this->loaded = true;
	}

	/**
	 * returns cache key
	 *
	 * @return string
	 */
	private function getCacheKey(): string
	{
		// slashes are not supported by get_user_meta
		return str_replace('\\', '', get_class($this) . '_' . $this->id);
	}

	/**
	 * returns true when notice can be displayed
	 *
	 * @return boolean
	 */
	private function canDisplay(): bool
	{
		if (!is_admin()) {
			return false;
		}

		if (empty($this->notices) && (!$this->displayWithoutNotices || empty($this->title))) {
			return false;
		}

		if ($this->dismissed) {
			return false;
		}

		return $this->createdAt <= 0 || $this->delay <= 0 || strtotime("-{$this->delay} seconds") >= $this->createdAt;
	}

	/**
	 * displays notices
	 *
	 * @return void
	 */
	private function displayNotices(): void
	{
		$noticeBoxId = $this->id . '_box';
		$noticeBoxClass = 'oneteamsoftware notice notice-' . $this->type . ' ' . $this->type;
		if ($this->permanent && $this->dismissible) {
			$noticeBoxClass .= ' is-dismissible';
		}

		echo '<div id="' . esc_attr($noticeBoxId) . '" class="' . esc_attr($noticeBoxClass) . '">';
		$nonce = wp_create_nonce($this->dismissAction);

		if ($this->permanent && $this->dismissible) {
			echo '<button class="notice-dismiss" onclick="jQuery.post(ajaxurl, {action: \'' . esc_attr($this->dismissAction) . '\', nonce: \'' . esc_attr($nonce) . '\'}).done(function() { jQuery(\'#' . esc_attr($noticeBoxId) . '\').slideUp(); });">'; // phpcs:ignore
			echo '<span class="screen-reader-text">';
			echo _e('Dismiss', $this->id);
			echo '</span>';
			echo '</button>';
		}

		if (false === empty($this->title)) {
			echo '<p><strong>';
			echo wp_kses_post($this->title);
			echo '</strong></p>';
		}

		foreach ((array)$this->notices as $notice) {
			echo '<p>';
			echo  wp_kses_post($notice);
			echo '</p>';
		}

		echo '</div>';
	}
}
