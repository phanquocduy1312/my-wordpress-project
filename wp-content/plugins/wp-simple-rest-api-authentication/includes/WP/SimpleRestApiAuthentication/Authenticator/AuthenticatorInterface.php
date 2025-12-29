<?php

declare(strict_types=1);

namespace OneTeamSoftware\WP\SimpleRestApiAuthentication\Authenticator;

use OneTeamSoftware\Logger\LoggerInterface;

interface AuthenticatorInterface
{
	/**
	 * AuthenticatorInterface constructor.
	 *
	 * @param LoggerInterface $logger
	 */
	public function __construct(LoggerInterface $logger);

	/**
	 * authenticates user by consumer key and secret provided via basic auth header
	 *
	 * @return int
	 */
	public function authenticate(): int;
}
