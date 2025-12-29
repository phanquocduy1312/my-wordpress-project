<?php

declare(strict_types=1);

namespace OneTeamSoftware\WP\SimpleRestApiAuthentication;

use OneTeamSoftware\Logger\LoggerInterface;
use OneTeamSoftware\WP\SimpleRestApiAuthentication\Authenticator\AuthenticatorInterface;
use OneTeamSoftware\WP\SimpleRestApiAuthentication\Authenticator\BasicAuthenticator;

class AuthenticationService
{
	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var AuthenticatorInterface
	 */
	private $authenticator;

	/**
	 * @var bool
	 */
	private $requireSSL;

	/**
	 * constructor
	 *
	 * @param LoggerInterface $logger
	 */
	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
		$this->authenticator = null;
		$this->requireSSL = true;
	}

	/**
	 * registers plugin
	 *
	 * @return void
	 */
	public function register(): void
	{
		add_filter('determine_current_user', [$this, 'authenticate'], PHP_INT_MAX, 1);
	}

	/**
	 * sets require SSL and returns itself
	 *
	 * @param bool $requireSSL
	 * @return AuthenticatorInterface
	 */
	public function withRequireSSL(bool $requireSSL): AuthenticationService
	{
		$this->requireSSL = $requireSSL;

		return $this;
	}

	/**
	 * authenticates a user if it is a request to REST API
	 *
	 * @param int $userId
	 * @return int|false
	 */
	public function authenticate(int $userId)
	{
		if (false === empty($userId) || false === $this->isRequestToRestApi()) {
			return $userId;
		}

		if ($this->requireSSL && false === is_ssl()) {
			$this->logger->debug(__FILE__, __LINE__, 'SSL is required for REST API authentication');
			return false;
		}

		$userId = $this->getAuthenticator()->authenticate();

		$this->logger->debug(__FILE__, __LINE__, 'user %d has been authenticated to REST API', $userId);

		return $userId !== 0 ? $userId : false;
	}

	/**
	 * checks if request is to REST API
	 *
	 * @return bool
	 */
	private function isRequestToRestApi(): bool
	{
		if (empty($_SERVER['REQUEST_URI'])) {
			return false;
		}

		$restPrefix = trailingslashit(rest_get_url_prefix());
		$requestUri = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']));

		return false !== strpos($requestUri, $restPrefix);
	}

	/**
	 * returns authenticator
	 *
	 * @return AuthenticatorInterface
	 */
	private function getAuthenticator(): AuthenticatorInterface
	{
		if (isset($this->authenticator)) {
			return $this->authenticator;
		}

		$this->authenticator = new BasicAuthenticator($this->logger);

		return $this->authenticator;
	}
}
