<?php
//© 2016 Martin Madsen

//setup the MTS tool kit for use

//set the base path to MTS
if (defined('MTS_BASE_PATH') === false) {
	$mtsBasePath	= realpath(dirname(__FILE__));
	define('MTS_WORK_PATH', $mtsBasePath . DIRECTORY_SEPARATOR . "WorkDirectory");

	$mtsBasePath	= rtrim($mtsBasePath, "MTS" . DIRECTORY_SEPARATOR);
	define('MTS_BASE_PATH', $mtsBasePath);
	
	//register the autoloader
	spl_autoload_register(function($className)
	{
		if (class_exists($className) === false) {
			$classPath		= array_values(array_filter(explode('\\', $className)));
			$vendor			= $classPath[0];
			if ($vendor == "MTS") {
				$filePath	= MTS_BASE_PATH . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . ".php";
				if (is_readable($filePath)) {
					require_once $filePath;
				}
			}
		}
	});
}