<?php
spl_autoload_register(function ($class) {
	if (strpos($class, 'job') === 0) {
		$class = str_replace('job\\', '', $class);
		$classPath = str_replace('\\', '/', $class);
		include __DIR__ . '/' . $classPath . '.php';
	}
});