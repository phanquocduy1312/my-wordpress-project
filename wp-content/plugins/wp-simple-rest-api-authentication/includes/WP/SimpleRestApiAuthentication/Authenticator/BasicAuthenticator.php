<?php

declare(strict_types=1);

namespace OneTeamSoftware\WP\SimpleRestApiAuthentication\Authenticator;

use OneTeamSoftware\Logger\LoggerInterface;

class BasicAuthenticator implements AuthenticatorInterface
{
	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * AuthenticatorInterface constructor.
	 *
	 * @param LoggerInterface $logger
	 */
	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * authenticates user by consumer key and secret provided via basic auth header
	 *
	 * @return int
	 */
	public function authenticate(): int
	{
		[$inConsumerKey, $inConsumerSecret] = $this->getInputConsumerKeyAndSecret();
		if (empty($inConsumerKey) || empty($inConsumerSecret)) {
			$this->logger->debug(__FILE__, __LINE__, 'no consumer key or secret provided via basic auth header');
			return 0;
		}

		$userData = $this->getUserDataByConsumerKey($inConsumerKey);
		if (empty($userData)) {
			$this->logger->debug(__FILE__, __LINE__, 'no user found for consumer key ' . $inConsumerKey);
			return 0;
		}

		if (false === hash_equals($userData['consumer_secret'] ?? '', sanitize_text_field($inConsumerSecret))) {
			$this->logger->debug(__FILE__, __LINE__, 'consumer secret does not match for consumer key ' . $inConsumerKey);
			return 0;
		}

		$userId = intval($userData['user_id'] ?? 0);
		$this->logger->debug(__FILE__, __LINE__, 'user %d authenticated via consumer key %s', $userId, $inConsumerKey);

		return $userId;
	}

	/**
	 * returns consumer key and secret provided via basic auth header
	 *
	 * @return array
	 */
	private function getInputConsumerKeyAndSecret(): array
	{
		if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
			return ['', ''];
		}

		$consumerKey = sanitize_text_field($_SERVER['PHP_AUTH_USER']);
		$consumerSecret = sanitize_text_field($_SERVER['PHP_AUTH_PW']);

		return [$consumerKey, $consumerSecret];
	}

	/**
	 * returns user data by consumer key
	 *
	 * @param string $consumerKey
	 * @return string
	 */
	private function getUserDataByConsumerKey(string $consumerKey): array
	{
		if (empty($consumerKey) || false === function_exists('wc_api_hash')) {
			$this->logger->debug(__FILE__, __LINE__, 'no consumer key or wc_api_hash function not found');
			return '';
		}

		global $wpdb;

		$hash = wc_api_hash(sanitize_text_field($consumerKey));
		$query = $wpdb->prepare(
			"
				SELECT key_id, user_id, permissions, consumer_key, consumer_secret, nonces
				FROM {$wpdb->prefix}woocommerce_api_keys
				WHERE consumer_key = %s
			",
			$hash
		);

		return $wpdb->get_row($query, ARRAY_A) ?? '';
	}
}
