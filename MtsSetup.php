<?php
//© 2016 Martin Madsen

ini_set('max_execution_time', 60);

if (php_sapi_name() == "cli") {
	//if run from the CLI
	$mtsPath		= trim(realpath(dirname(__FILE__)))  . DIRECTORY_SEPARATOR . "MTS";;
	$mtsInclude		= $mtsPath . DIRECTORY_SEPARATOR . "EnableMTS.php";
	require_once $mtsInclude;
	
	$setupClass		= new \MTS\Common\Devices\Actions\Local\Host\MtsSetup\SetupMTS();

	echo "\nWe do not know which user you intend to execute the scripts with\n";
	echo "All example commands use '".$setupClass->getRunUser()."' as the user, please replace with\n";
	echo "the username you intent to use.\n\n\n";
	
	$results		= array_reverse($setupClass->getInstall());
	foreach ($results as $result) {
		echo strtoupper($result["msgHead"]);
		echo "\n";
		echo implode("\n", $result["msgLines"]);
		echo "\n\n\n";
	}

	exit;
	//end of cli version
} else {
	
	//start Web version		
	$pathForm	= '<!-- © 2016 Martin Madsen  -->
				<form name="setPath" method="post" action="" >
				    <center>
				    <table>
				        <tr>
				        <td><b>Absolute Path to the directory that holds the MTS folder:</b></td>
				        <td width="300"><input type="text" id="mtsBasePath" name="mtsBasePath" size="60"/></td>
				        <td> <input type="Submit" value="Setup" /></td>
				        </tr>
				    </table>
				    </center>
				</form>
				';
	
	echo $pathForm;

	//fill the mtsBasePath input box again
	if (array_key_exists('mtsBasePath', $_POST) === true && strlen($_POST['mtsBasePath']) > 0) {
		$mtsBasePath	= trim($_POST['mtsBasePath']);
		$mtsBasePath	= rtrim($mtsBasePath, DIRECTORY_SEPARATOR);

		echo "<script>document.getElementById('mtsBasePath').value = '".$mtsBasePath."';</script>";
	} else {
		$mtsBasePath	= trim(realpath(dirname(__FILE__)));
		$mtsBasePath	= rtrim($mtsBasePath, DIRECTORY_SEPARATOR);
		
		echo "<script>document.getElementById('mtsBasePath').value = '".$mtsBasePath."';</script>";
	}
	
	$baseExists	= file_exists($mtsBasePath);
	if ($baseExists === true) {
		$mtsPath			= $mtsBasePath . DIRECTORY_SEPARATOR . "MTS";
		$mtsPathExists	= file_exists($mtsPath);
		if ($mtsPathExists === false) {
			echo "<br><center><font color='#FF0000'><h2>The base path you specified exists, however it does not have a directory named 'MTS' inside</h2></center><br>";
			//exit we must have a base path
			exit;
		}
		
	} else {
		echo "<br><center><font color='#FF0000'><h2>The base path you specified does not exist</h2></center><br>";
		//exit we must have a base path
		exit;
	}

	error_reporting(E_ALL);
	ini_set('display_errors', true);
	
	$mtsInclude		=  $mtsPath . DIRECTORY_SEPARATOR . "EnableMTS.php";
	require_once $mtsInclude;
	
	$setupClass		= new \MTS\Common\Devices\Actions\Local\Host\MtsSetup\SetupMTS();
	$results		= array_reverse($setupClass->getInstall());
	
	echo "<center>
			<table border=1>
			";
	
	foreach ($results as $result) {
		
		if (strtolower($result["errorLevel"]) == "error") {
			$color	= "FF0000";
		} elseif (strtolower($result["errorLevel"]) == "warning") {
			$color	= "FFFF00";
		} elseif (strtolower($result["errorLevel"]) == "info") {
			$color	= "7FFF00";
		} else {
			$color	= "66CD00";
		}

		echo "<tr>";
		echo "<th width='800' bgcolor='#".$color."'>".$result["msgHead"]."</th>";
		echo "</tr>";
		
		echo "<tr>";
		echo "<td bgcolor='#".$color."'>" . implode("<br>", $result["msgLines"]) . "</th>";
		echo "</tr>";
	}
	
	echo "</table>
			</center>
			<br><br>
			";
	
	exit;
	//end of web version
}
?>

<!-- PHP not installed or module not loaded in webserver -->
<center><H3>PHP NOT INSTALLED</H3></center>

