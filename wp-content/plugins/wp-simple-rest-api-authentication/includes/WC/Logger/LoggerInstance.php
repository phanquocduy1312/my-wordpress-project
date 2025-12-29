<?php

declare(strict_types=1);

namespace OneTeamSoftware\WC\Logger;

class LoggerInstance
{
	/**
	 * @var array
	 */
	private static $instances = [];

	/**
	 * returns instance of a logger for a given id
	 *
	 * @param string $id
	 * @return Logger
	 */
	public static function getInstance(string $id): Logger
	{
		if (empty(self::$instances[$id])) {
			self::$instances[$id] = new Logger($id);
		}

		return self::$instances[$id];
	}
}
