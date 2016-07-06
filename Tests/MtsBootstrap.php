<?php
//© 2016 Martin Madsen

//Tests that involve browser and remote shells can take a long time
ini_set('max_execution_time', 180);

//include MTS
$curPath		= realpath(dirname(__FILE__));
$dirs			= array_filter(explode(DIRECTORY_SEPARATOR, $curPath));
array_pop($dirs);
$mtsPath		= implode(DIRECTORY_SEPARATOR, $dirs);
$mtsEnablePath	= DIRECTORY_SEPARATOR . $mtsPath . DIRECTORY_SEPARATOR . "MTS" . DIRECTORY_SEPARATOR . "EnableMTS.php";

require_once $mtsEnablePath;

//include real devices if any
require_once $curPath . DIRECTORY_SEPARATOR . "MtsUnitTestDevices.php";

//set real remote device for testing, if you like. many of the Actions cannot be tested against local host
\MtsUnitTestDevices::$hostname			= "";
\MtsUnitTestDevices::$username			= "";
\MtsUnitTestDevices::$password			= "";
\MtsUnitTestDevices::$connType			= "ssh";
\MtsUnitTestDevices::$connPort			= 22;
//Leave $deviceCache true, unless you want each test to open its own connection
\MtsUnitTestDevices::$deviceCache		= true;

//if you want to test changing a non root shell to another user (maybe root), set the username and password here
\MtsUnitTestDevices::$switchUsername	= "";
\MtsUnitTestDevices::$switchPassword	= "";

//to run all tests execute:
//phpunit -c MtsPhpUnit.xml