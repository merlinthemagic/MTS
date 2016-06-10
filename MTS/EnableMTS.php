<?php
//© 2016 Martin Madsen

//setup the MTS (Merlin Tool Set) for use

//set the base path to MTS
if (defined('MTS_BASE_PATH') === false) {
	$mtsBasePath	= realpath(dirname(__FILE__));
	
	//this is the directory used for all temp files. There seems to be a problem
	//with file attributes being updated quickly enough when the underlying FS is tempFS like /tmp/
	$mtsWorkPath	= $mtsBasePath . DIRECTORY_SEPARATOR . "WorkDirectory";
	define('MTS_WORK_PATH', $mtsWorkPath);

	$mtsClassPath	= rtrim($mtsBasePath, "MTS" . DIRECTORY_SEPARATOR);
	define('MTS_BASE_PATH', $mtsClassPath);
	
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
	
	//set execution start microtime
	define('MTS_EXECUTION_START', \MTS\Factories::getTime()->getEpochTool()->getCurrentMiliTime());
}