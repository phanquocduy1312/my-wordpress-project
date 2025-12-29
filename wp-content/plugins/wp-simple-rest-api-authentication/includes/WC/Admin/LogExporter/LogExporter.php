<?php

declare(strict_types=1);

namespace OneTeamSoftware\WC\Admin\LogExporter;

use WC_Log_Handler_File;

class LogExporter
{
	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var string
	 */
	private $secret;

	/**
	 * constructor
	 *
	 * @param string $id
	 * @param string $secret
	 */
	public function __construct(string $id, string $secret)
	{
		$this->id = $id;
		$this->secret = $secret;
	}

	/**
	 * register
	 *
	 * @return void
	 */
	public function register(): void
	{
		add_action('init', [$this, 'onExport']);
	}

	/**
	 * returns export URL
	 *
	 * @return string
	 */
	public function getExportUrl(): string
	{
		return admin_url(sprintf('?export_log=%s&nonce=%s', $this->id, wp_create_nonce($this->secret)));
	}

	/**
	 * exports debug log
	 *
	 * @return void
	 */
	public function onExport(): void
	{
		if ($this->skipExport()) {
			return;
		}

		$filePath = WC_Log_Handler_File::get_log_file_path($this->id);
		if (
			false === file_exists($filePath) ||
			false === is_readable($filePath) ||
			false === preg_match('/\.log$/', $filePath)
		) {
			return;
		}

		$this->sendHttpHeaders('plain', basename($filePath));

		echo file_get_contents(realpath($filePath));

		exit;
	}

	/**
	 * returns true when export should be skipped
	 *
	 * @return bool
	 */
	private function skipExport(): bool
	{
		if (headers_sent()) {
			return true;
		}

		if (($_REQUEST['export_log'] ?? '') !== $this->id) {
			return true;
		}

		if (
			empty($_REQUEST['nonce']) ||
			false === wp_verify_nonce(sanitize_key($_REQUEST['nonce']), $this->secret)
		) {
			return true;
		}

		if (false === current_user_can('administrator')) {
			return true;
		}

		return false;
	}

	/**
	 * sends headers
	 *
	 * @param string $type
	 * @param string $filename
	 * @return void
	 */
	private function sendHttpHeaders(string $type, string $filename): void
	{
		if (function_exists('gc_enable')) {
			gc_enable(); // phpcs:ignore PHPCompatibility.PHP.NewFunctions.gc_enableFound
		}
		if (function_exists('apache_setenv')) {
			@apache_setenv('no-gzip', '1'); // @codingStandardsIgnoreLine
		}
		@ini_set('zlib.output_compression', 'Off'); // @codingStandardsIgnoreLine
		@ini_set('output_buffering', 'Off'); // @codingStandardsIgnoreLine
		@ini_set('output_handler', ''); // @codingStandardsIgnoreLine
		ignore_user_abort(true);
		wc_set_time_limit(0);
		wc_nocache_headers();
		header('Content-Type: text/' . $type . '; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $filename);
		header('Pragma: no-cache');
		header('Expires: 0');
	}
}
