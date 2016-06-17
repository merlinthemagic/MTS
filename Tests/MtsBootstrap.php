<?php
//© 2016 Martin Madsen

//tests that involve browser and shell can take a long time
ini_set('max_execution_time', 120);

//include MTS
$curPath		= realpath(dirname(__FILE__));
$dirs			= array_filter(explode(DIRECTORY_SEPARATOR, $curPath));
array_pop($dirs);
$mtsPath		= implode(DIRECTORY_SEPARATOR, $dirs);
$mtsEnablePath	= DIRECTORY_SEPARATOR . $mtsPath . DIRECTORY_SEPARATOR . "MTS" . DIRECTORY_SEPARATOR . "EnableMTS.php";

require_once $mtsEnablePath;

//to run all tests execute:
//phpunit -c MtsPhpUnit.xml

//local and remote actions are tested in the same file, because we cannot redeclare a class name even on seperate path