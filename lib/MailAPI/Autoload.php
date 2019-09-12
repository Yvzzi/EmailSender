<?php

if (file_exists(__DIR__ . "/simpleload.lock") && file_exists(__DIR__ . "/Depload.php")) require_once __DIR__ . "/Depload.php";

spl_autoload_register(function ($class) {
	$prefix = "";
	$base_dir = __DIR__ . "/src/" ;
	// does the class use the namespace prefix
	$len = strlen($prefix);
	if (strncmp($prefix, $class, $len) !== 0) {
		// no, move to the next registered autoloader
		return;
	}
	$relative_class = substr($class, $len);
	// Compatible with file in Linux. In Windows, / is the same as \.
	$file = str_replace('\\', '/', $base_dir . $relative_class) . '.php';
    // echo "Loading " . $file . PHP_EOL;
	if (file_exists($file)) {
		require_once($file);
	}
});
?>