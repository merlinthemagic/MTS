<?php
//© 2016 Martin Madsen

//include MTS
$curPath		= realpath(dirname(__FILE__));
$dirs			= array_filter(explode(DIRECTORY_SEPARATOR, $curPath));
array_pop($dirs);
$mtsPath		= implode(DIRECTORY_SEPARATOR, $dirs);
$mtsEnablePath	= DIRECTORY_SEPARATOR . $mtsPath . DIRECTORY_SEPARATOR . "MTS" . DIRECTORY_SEPARATOR . "EnableMTS.php";

require_once $mtsEnablePath;

//to run all tests execute:
//phpunit -c phpunit.xml