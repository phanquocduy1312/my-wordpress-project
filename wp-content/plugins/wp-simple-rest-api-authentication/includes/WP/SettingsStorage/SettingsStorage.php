<?php

declare(strict_types=1);

namespace OneTeamSoftware\WP\SettingsStorage;

use OneTeamSoftware\Mutex\MutexInterface;

class SettingsStorage
{
	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var MutexInterface
	 */
	private $mutex;

	/**
	 * constructor
	 *
	 * @param string $id
	 * @param MutexInterface $mutex
	 */
	public function __construct(string $id, MutexInterface $mutex)
	{
		$this->id = $id;
		$this->mutex = $mutex;
	}

	/**
	 * updates settings
	 *
	 * @param array $settings
	 * @return bool
	 */
	public function update(array $settings): bool
	{
		if (false === $this->mutex->lock()) {
			return false;
		}

		update_option($this->id, array_merge($this->get(), $settings));

		$this->mutex->unlock();

		do_action($this->id . '_settingsstorage_updated', $settings);

		return true;
	}

	/**
	 * returns settings
	 *
	 * @return array
	 */
	public function get(): array
	{
		$settings = get_option($this->id, []);

		return apply_filters($this->id . '_settingsstorage_get', $settings ? $settings : []);
	}

	/**
	 * returns true when settings have been deleted
	 *
	 * @return bool
	 */
	public function delete(): bool
	{
		$success = delete_option($this->id);

		do_action($this->id . '_settingsstorage_deleted');

		return $success;
	}
}
