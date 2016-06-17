<?php
//© 2016 Martin Madsen

//Actions local and remote are tested in the same file, because we cannot redeclare a class name even on seperate path
//Tests that involve browser and shell can take a long time even if remote devices are not used
ini_set('max_execution_time', 120);

//include MTS
$curPath		= realpath(dirname(__FILE__));
$dirs			= array_filter(explode(DIRECTORY_SEPARATOR, $curPath));
array_pop($dirs);
$mtsPath		= implode(DIRECTORY_SEPARATOR, $dirs);
$mtsEnablePath	= DIRECTORY_SEPARATOR . $mtsPath . DIRECTORY_SEPARATOR . "MTS" . DIRECTORY_SEPARATOR . "EnableMTS.php";

require_once $mtsEnablePath;

//include real devices if any
require_once $curPath . DIRECTORY_SEPARATOR . "MtsUnitTestDevices.php";

//set a generic remote device if you want to test a specific device
\MtsUnitTestDevices::$genericHostname	= "";
\MtsUnitTestDevices::$genericUsername	= "";
\MtsUnitTestDevices::$genericPassword	= "";
\MtsUnitTestDevices::$genericConnType	= "ssh";
\MtsUnitTestDevices::$genericConnPort	= 22;
\MtsUnitTestDevices::$genericCache		= false;

//set ROS remote device if you want to test a specific device
\MtsUnitTestDevices::$rosHostname		= "";
\MtsUnitTestDevices::$rosUsername		= "";
\MtsUnitTestDevices::$rosPassword		= "";
\MtsUnitTestDevices::$rosConnType		= "ssh";
\MtsUnitTestDevices::$rosConnPort		= 22;
\MtsUnitTestDevices::$rosCache			= false;
//set cache to true to avoid every test opening its own connection

//to run all tests execute:
//phpunit -c MtsPhpUnit.xml