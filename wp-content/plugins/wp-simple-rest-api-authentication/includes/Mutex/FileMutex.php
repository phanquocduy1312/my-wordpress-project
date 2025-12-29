<?php

declare(strict_types=1);

namespace OneTeamSoftware\Mutex;

class FileMutex implements MutexInterface
{
	/**
	 * @var string
	 */
	private $filePath;

	/**
	 * @var resource
	 */
	private $fileHandler;

	/**
	 * @var bool
	 */
	private $locked;

	/**
	 * constructor
	 *
	 * @param string $name
	 */
	public function __construct(string $name)
	{
		$this->filePath = sys_get_temp_dir() . '/' . hash('sha256', $name) . '.lock';
		$this->fileHandler = null;
		$this->locked = false;
	}

	/**
	 * destructor
	 */
	public function __destruct()
	{
		$this->unlock();
		$this->closeFile();
	}

	/**
	 * locks as blocking or non blocking depending on the parameter
	 *
	 * @param bool $nonBlocking
	 * @return bool
	 */
	public function lock(bool $nonBlocking = false): bool
	{
		if ($this->locked) {
			return true;
		}

		if (false === $this->openFile()) {
			return false;
		}

		$operation = $nonBlocking ? LOCK_EX | LOCK_NB : LOCK_EX;

		$this->locked = flock($this->fileHandler, $operation);

		return $this->locked;
	}

	/**
	 * unlocks a mutex
	 *
	 * @return bool
	 */
	public function unlock(): bool
	{
		if (false === $this->locked || false === is_resource($this->fileHandler)) {
			return false;
		}

		$success = flock($this->fileHandler, LOCK_UN);

		$this->locked = false;

		return $success;
	}

	/**
	 * closes file
	 *
	 * @return void
	 */
	private function closeFile(): void
	{
		if (is_resource($this->fileHandler)) {
			fclose($this->fileHandler);
		}

		if (file_exists($this->filePath)) {
			@unlink($this->filePath);
		}
	}

	/**
	 * opens file and returns true in case of a success
	 *
	 * @return bool
	 */
	private function openFile(): bool
	{
		if (is_resource($this->fileHandler)) {
			return true;
		}

		$this->fileHandler = fopen($this->filePath, 'w+');

		return is_resource($this->fileHandler);
	}
}
