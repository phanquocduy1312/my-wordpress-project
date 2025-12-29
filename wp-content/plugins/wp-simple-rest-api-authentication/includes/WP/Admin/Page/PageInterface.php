<?php

declare(strict_types=1);

namespace OneTeamSoftware\WP\Admin\Page;

interface PageInterface
{
	/**
	 * Renders admin page
	 *
	 * @return void
	 */
	public function display(): void;
}
