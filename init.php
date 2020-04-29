<?php

set_include_path(__DIR__ . '/classes');
date_default_timezone_set('UTC');

spl_autoload_register(function ($class) {
	$path = str_replace('\\', '/', ltrim($class, '\\')) . '.php';
	if ($fullPath = stream_resolve_include_path($path)) {
		include_once $fullPath;
	}
});

if (is_file($file = __DIR__ . '/db_cfg.php')) {
	include $file;
}