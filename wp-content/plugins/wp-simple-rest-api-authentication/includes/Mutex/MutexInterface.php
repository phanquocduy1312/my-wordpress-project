<?php

declare(strict_types=1);

namespace OneTeamSoftware\Mutex;

interface MutexInterface
{
	/**
	 * constructor
	 *
	 * @param string $name
	 */
	public function __construct(string $name);

	/**
	 * locks as blocking or non blocking depending on the parameter
	 *
	 * @param bool $nonBlocking
	 * @return bool
	 */
	public function lock(bool $nonBlocking = false): bool;

	/**
	 * unlocks a mutex
	 *
	 * @return bool
	 */
	public function unlock(): bool;
}
