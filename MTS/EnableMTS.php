<?php
//© 2016 Martin Madsen

//setup the MTS (Merlin Tool Set) for use

//set the base path to MTS
if (defined('MTS_BASE_PATH') === false) {
	$mtsBasePath	= realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
	
	//this is the directory used for all temp files. There seems to be a problem
	//with file attributes being updated quickly enough when the underlying FS is tempFS like /tmp/
	$mtsWorkPath	= $mtsBasePath . "WorkDirectory";
	define('MTS_WORK_PATH', $mtsWorkPath);

	$mtsClassPath	= realpath($mtsBasePath . "..") . DIRECTORY_SEPARATOR;
	define('MTS_BASE_PATH', $mtsClassPath);

	//register the autoloader
	spl_autoload_register(function($className)
	{
		if (class_exists($className) === false) {
			$classPath		= array_values(array_filter(explode('\\', $className)));
			$vendor			= $classPath[0];
			if ($vendor == "MTS") {
				$filePath	= MTS_BASE_PATH . str_replace('\\', DIRECTORY_SEPARATOR, $className) . ".php";
				if (is_readable($filePath)) {
					require_once $filePath;
				}
			}
		}
	});
	
	//prep the environment
	mtsEnvironmentalSetup();
}

function mtsEnvironmentalSetup()
{
	$osObj	= \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
	if ($osObj->getType() != "Linux" && $osObj->getType() != "Windows") {
		throw new \Exception("MTS does not support OS Type: " . $osObj->getType());
	}
	
	//set execution start microtime
	define('MTS_EXECUTION_START', \MTS\Factories::getTime()->getEpochTool()->getCurrentMiliTime());
	
	if (getenv("PATH") === false) {
		//make sure the environment PATH variable is set, on nginx it is not.
		if (function_exists("exec") === true) {
			putenv("PATH=" . trim(exec("echo \$PATH")));
		}
	}
}