<?php

declare(strict_types=1);

namespace OneTeamSoftware\AutoLoader;

if (false === class_exists(__NAMESPACE__ . '\\AutoLoader')) :

	class AutoLoader
	{
		/**
		 * @var string
		 */
		protected $namespace;

		/**
		 * @var string
		 */
		protected $includePath;

		/**
		 * constructor
		 *
		 * @param string $includePath
		 * @param string $namespace
		 */
		public function __construct(string $includePath, string $namespace)
		{
			$this->namespace = trim($namespace, '\\') . '\\';
			$this->includePath = $includePath;
		}

		/**
		 * autoloads a given class
		 *
		 * @param string $class
		 * @return void
		 */
		public function autoload(string $class): void
		{
			if (strpos($class, $this->namespace) === 0) {
				$filePath = $this->includePath . '/' .
				str_replace('\\', '/', substr($class, strlen($this->namespace))) . '.php';

				if (file_exists($filePath)) {
					include_once($filePath);
				}
			}
		}

		/**
		 * registers
		 *
		 * @return void
		 */
		public function register(): void
		{
			spl_autoload_register([$this, 'autoload']);
		}
	}

endif;
