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

//set real remote device for testing, if you like. many of the Actions cannot be tested against local host
\MtsUnitTestDevices::$hostname			= "";
\MtsUnitTestDevices::$username			= "";
\MtsUnitTestDevices::$password			= "";
\MtsUnitTestDevices::$connType			= "ssh";
\MtsUnitTestDevices::$connPort			= 22;
//Leave $deviceCache true, unless you want each test to open its own connection
\MtsUnitTestDevices::$deviceCache		= true;
//Leave $deviceType empty, unless you are connecting to a ROS device, then set it to "ros"
\MtsUnitTestDevices::$deviceType		= "";

//if you want to test changing local shell user to another (maybe root), set the username and password here
\MtsUnitTestDevices::$switchUsername	= "";
\MtsUnitTestDevices::$switchPassword	= "";

//to run all tests execute:
//phpunit -c MtsPhpUnit.xml