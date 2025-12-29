<?php

declare(strict_types=1);

namespace OneTeamSoftware\Logger;

class NullLogger extends AbstractLogger
{
	/**
	 * does not log anything
	 *
	 * @param string $level
	 * @param string $file
	 * @param int $line
	 * @param string $message
	 * @param mixed ...$args
	 * @return void
	 */
	public function log(string $level, string $file, int $line, string $message, ...$args): void
	{
		// do nothing
	}
}
