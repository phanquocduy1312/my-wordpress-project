<?php

namespace OneTeamSoftware\AutoLoader;

if (!class_exists(__NAMESPACE__ . '\\AutoLoader') && file_exists(__DIR__ . '/AutoLoader/AutoLoader.php')) {
	require_once(__DIR__ . '/AutoLoader/AutoLoader.php');
}

if (class_exists(__NAMESPACE__ . '\\AutoLoader')) {
	(new AutoLoader(__DIR__, 'OneTeamSoftware\\WooCommerce\\'))->register();
	(new AutoLoader(__DIR__, 'OneTeamSoftware\\'))->register();
}
