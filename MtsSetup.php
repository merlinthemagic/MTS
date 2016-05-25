<?php
//© 2016 Martin Madsen

	function localShellExec($cmdString)
	{
		exec($cmdString, $rData);
		$cReturn	= implode("\n", $rData);
		return $cReturn;
	}
	function getOS()
	{
		return \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
	}
	function getPythonExe()
	{
		return \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile('python');
	}
	function getScreenExe()
	{
		return \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile('screen');
	}
	function getSudoExe()
	{
		return \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile('sudo');
	}
	function getWebserverUsername()
	{
		return \MTS\Factories::getActions()->getLocalOperatingSystem()->getUsername();
	}
	function execEnabled()
	{
		if (function_exists('exec') === false) {
			return false;
		} else {
			return true;
		}
	}
	function timezoneSet()
	{
		$timezone	= trim(ini_get('date.timezone'));
		if ($timezone == "") {
			return false;
		} else {
			return true;
		}
	}
	function workDirectoryWritable()
	{
		$rwWorkDir	= is_writable(MTS_WORK_PATH);
		if ($rwWorkDir === true) {
			return true;
		} else {
			return false;
		}
	}
	function getPhpIni()
	{
		$iniLocation	= php_ini_loaded_file();
		$dirs			= explode(DIRECTORY_SEPARATOR, $iniLocation);
		$fileName		= array_pop($dirs);
		$exePath		= implode(DIRECTORY_SEPARATOR, $dirs);
		
		return \MTS\Factories::getFiles()->getFile($fileName, $exePath);
	}
	function getLocalHost()
	{
		return \MTS\Factories::getDevices()->getLocalHost();
	}
	function sudoPythonEnabled()
	{
		return \MTS\Factories::getActions()->getLocalApplicationPaths()->getSudoEnabled('python');
	}

	if (php_sapi_name() == "cli") {
		//if run from the CLI
		$mtsPath		= trim(realpath(dirname(__FILE__)))  . DIRECTORY_SEPARATOR . "MTS";;
		$mtsInclude		= $mtsPath . DIRECTORY_SEPARATOR . "EnableMTS.php";
		require_once $mtsInclude;
		
		function isTimezoneSet()
		{
			$enabled	= timezoneSet();
			if ($enabled === false) {
				echo "\nPHP does not have its timezone set. This is required.";
				echo "\nFind the line: ';date.timezone =' in ".getPhpIni()->getPathAsString()."";
				echo "\nChange it by removing the ';' in front and set the value to reflect your time zone.";
				echo "\nFor Los Angeles that would be: 'date.timezone = America/Los_Angeles'\n\n";
				exit;
			}
		}
		function enabledExec()
		{
			$enabled	= execEnabled();
			if ($enabled === false) {
				echo "\nThe PHP function exec() is not enabled and that is required.";
				echo "\nEnable it to continue.\n";
				exit;
			}
		}
		function isWorkWritable()
		{
			echo "\nThe Work directory must be writable for the user that runs PHP.";
			echo "\nPlease make sure ".MTS_WORK_PATH." is writable by that user.";

			echo "\n\nChoose from the following options..\n\n";
			echo "1. I am sure the path is writable\n";
			echo "2. How do i make sure?\n";
			echo "Enter your choice:";
			$choice = trim(fgets(STDIN));
				
			if ($choice == 1) {
			
			} elseif ($choice == 2) {
			
				echo "\nExample if the user that runs php is apache then you can issue this command to be sure:\n";
				echo "chown -R apache:apache ".MTS_WORK_PATH."\n\n";
				exit;
			
			} else {
				echo "\nNot a valid choice.\n";
				exit;
			}
		}
		function installPython()
		{
			$pythonFile	= getPythonExe();
			if ($pythonFile === false) {
				echo "\nPython is not installed and is required.";
				echo "\nExecute the following command to install it:\n";
				
				$osName	= strtolower(getOS()->getName());
				if ($osName == 'centos' || $osName == 'red hat enterprise') {
					echo "yum -y install python\n\n";
				} elseif ($osName == 'debian' || $osName == 'ubuntu') {
					echo "apt-get -y install python\n\n";
				} elseif ($osName == 'arch') {
					echo "pacman -S python\n\n";
				} else {
					echo "Expand to handle OS name:" . getOS()->getName() . "\n\n";
				}
				exit;
			}
		}
		function installScreen()
		{
			$screenFile	= getScreenExe();
			if ($screenFile === false) {
				echo "\nScreen is not installed and is required.";
				echo "\nExecute the following command to install it:\n";
				
				$osName	= strtolower(getOS()->getName());
				if ($osName == 'centos' || $osName == 'red hat enterprise') {
					echo "yum -y install screen\n\n";
				} elseif ($osName == 'debian' || $osName == 'ubuntu') {
					echo "apt-get -y install screen\n\n";
				} elseif ($osName == 'arch') {
					echo "pacman -S python\n\n";
				} else {
					echo "Expand to handle OS name:" . getOS()->getName() . "\n\n";
				}
				exit;
			}
		}
		function shellPossible()
		{
			try {
				$shellObj		= getLocalHost()->getShell('bash', false);
				$username		= \MTS\Factories::getActions()->getRemoteOperatingSystem()->getUsername($shellObj);
			} catch (\Exception $e) {
				switch($e->getCode()){
					default;
					echo "\nWas unable to create a shell. Msg: " . $e->getMessage();
					exit;
				}
			}
		}
		
		function wantSudoPython()
		{
			echo "\nInfo: If the user that triggers a php script is not allowed to sudo python,";
			echo "\nthen you will only be able to instantiate non-root shells.";
			echo "\nYou will still be able to elevate those non-root shell to root by calling:";
			echo "\n\MTS\Factories::getActions()->getRemoteUsers()->changeShellUser(\$shellObj, 'root', 'rootPassword')";
			
			echo "\n\nChoose from the following options..\n\n";
			echo "1. Continue Without Sudo\n";
			echo "2. Setup Sudo\n";
			echo "Enter your choice:";
			$choice = trim(fgets(STDIN));
			
			if ($choice == 1) {
				
			} elseif ($choice == 2) {

				echo "\nWarning: Giving Sudo access to the webserver user is a security risk.";
				$sudoFile	= getSudoExe();
				if ($sudoFile === false) {
					echo "\nSudo is not installed and is required.";
					echo "\nExecute the following command to install it:\n";
					
					$osName	= strtolower(getOS()->getName());
					if ($osName == 'centos' || $osName == 'red hat enterprise') {
						echo "yum -y install sudo\n\n";
					} elseif ($osName == 'debian' || $osName == 'ubuntu') {
						echo "apt-get -y install sudo\n\n";
					} elseif ($osName == 'arch') {
						echo "pacman -S sudo\n\n";
					} else {
						echo "Expand to handle OS name:" . getOS()->getName() . "\n\n";
					}
					exit;
				}

				
				echo "\n\nEdit /etc/sudoers in the following way:";
				echo "\n1) Find this line: 'root    ALL=(ALL)       ALL'";
				echo "\nAdd this line after it: '\$phpUser ALL=(ALL)NOPASSWD:".getPythonExe()->getPathAsString()."', replace \$phpUser with the real username.";
				echo "\nExample if the user that runs php is apache then the line should look like this:";
				echo "\napache ALL=(ALL)NOPASSWD:".getPythonExe()->getPathAsString()."'";
				echo "\n";
				echo "\n2) Find this line (if it exists): 'Defaults    requiretty'";
				echo "\nComment out the line by adding a '#' in front of it.\n\n";
			} else {
				echo "\nNot a valid choice.\n";
				exit;
			}
		}

		isTimezoneSet();
		enabledExec();
		isWorkWritable();
		installPython();
		installScreen();
		shellPossible();
		wantSudoPython();
		
		echo "\nInstall Valid, Include this line when you need to use a shell:";
		echo "\nrequire_once \"".$mtsInclude."\";\n";
		
		echo "\nSuccessful setup. Exiting\n\n";
		
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
		
		$errorMsg	= "";
		$warningMsg	= "";
		
		
		if ($errorMsg == "") {
			$enabled	= timezoneSet();
			if ($enabled === false) {
				$errorMsg	.= "PHP does not have its timezone set. This is required.";
				$errorMsg	.= "<br>Find the line: ';date.timezone =' in ".getPhpIni()->getPathAsString()."";
				$errorMsg	.= "<br>Change it by removing the ';' in front and set the value to reflect your time zone.";
				$errorMsg	.= "<br>For Los Angeles that would be: 'date.timezone = America/Los_Angeles'";
			}
		}
		
		if ($errorMsg == "") {
			$enabled	= execEnabled();
			if ($enabled === false) {
				$errorMsg	.= "The PHP function exec() is not enabled and that is required.";
				$errorMsg	.= "<br>Enable it to continue.";
			}
		}
			
		if ($errorMsg == "") {
			$writable	= workDirectoryWritable();
			if ($writable === false) {
				$errorMsg	.= "PHP cannot write to the MTS WorkDirectory and that is required.";
				$errorMsg	.= "<br>Execute the following command to allow it:";
				$errorMsg	.= "<br>chown -R ".getWebserverUsername() .":".getWebserverUsername() ." ".MTS_WORK_PATH."";
			}
		}
		
		if ($errorMsg == "") {
			$pythonFile	= getPythonExe();
			if ($pythonFile === false) {
				$errorMsg	.= "Python is not installed and is required.";
				$errorMsg	.= "<br>Execute the following command to install it:<br>";
				
				$osName	= strtolower(getOS()->getName());
				if ($osName == 'centos' || $osName == 'red hat enterprise') {
					$errorMsg	.= "yum -y install python";
				} elseif ($osName == 'debian' || $osName == 'ubuntu') {
					$errorMsg	.= "apt-get -y install python";
				} elseif ($osName == 'arch') {
					$errorMsg	.= "pacman -S python";
				} else {
					$errorMsg	.= "Expand to handle OS name:" . getOS()->getName() . "";
				}
			}
		}
		
		if ($errorMsg == "") {
			$screenFile	= getScreenExe();
			if ($screenFile === false) {
				$errorMsg	.= "Screen is not installed and is required.";
				$errorMsg	.= "<br>Execute the following command to install it:<br>";
		
				$osName	= strtolower(getOS()->getName());
				if ($osName == 'centos' || $osName == 'red hat enterprise') {
					$errorMsg	.= "yum -y install screen";
				} elseif ($osName == 'debian' || $osName == 'ubuntu') {
					$errorMsg	.= "apt-get -y install screen";
				} elseif ($osName == 'arch') {
					$errorMsg	.= "pacman -S screen";
				} else {
					$errorMsg	.= "Expand to handle OS name:" . getOS()->getName() . "";
				}
			}
		}
		
		if ($errorMsg == "") {
			try {
				$shellObj		= getLocalHost()->getShell('bash', false);
				$username		= \MTS\Factories::getActions()->getRemoteOperatingSystem()->getUsername($shellObj);
			} catch (\Exception $e) {
				switch($e->getCode()){
					default;
					$errorMsg	.= "Was unable to create a shell. Msg: " . $e->getMessage();
				}
			}
		}

		if ($errorMsg == "") {
			
			$enabled	= sudoPythonEnabled();
			if ($enabled === false) {
				
				$warningMsg	.= "The ".getWebserverUsername()." user is not allowed to sudo python.";
				$warningMsg	.= "<br>This is not required, but optional, but you will only be able to instantiate non-root shells.";
				$warningMsg	.= "<br>You will still be able to elevate those non-root shell to root by calling:";
				$warningMsg	.= "<br>\MTS\Factories::getActions()->getRemoteUsers()->changeShellUser(\$shellObj, 'root', 'rootPassword')";
				
				$warningMsg	.= "<br><br>Warning: Giving Sudo access to the webserver user is a security risk.";
				$warningMsg	.= "<br>If you want to enable sudo follow the steps below.";

				$sudoFile	= getSudoExe();
				if ($sudoFile === false) {
					$warningMsg	.= "<br><br>Sudo is not installed and is required.";
					$warningMsg	.= "<br>Execute the following command to install it:<br>";
						
					$osName	= strtolower(getOS()->getName());
					if ($osName == 'centos' || $osName == 'red hat enterprise') {
						$warningMsg	.= "yum -y install sudo";
					} elseif ($osName == 'debian' || $osName == 'ubuntu') {
						$warningMsg	.= "apt-get -y install sudo";
					} elseif ($osName == 'arch') {
						$warningMsg	.= "pacman -S sudo";
					} else {
						$warningMsg	.= "Expand to handle OS name:" . getOS()->getName() . "";
					}
				}
				
				$warningMsg	.= "<br><br>Edit /etc/sudoers in the following way:";
				$warningMsg	.= "<br>1) Find this line: 'root    ALL=(ALL)       ALL'";
				$warningMsg	.= "<br>Add this line after it: '".getWebserverUsername()." ALL=(ALL)NOPASSWD:".getPythonExe()->getPathAsString()."'.";
				$warningMsg	.= "<br><br>2) Find this line (if it exists): 'Defaults    requiretty'";
				$warningMsg	.= "<br>Comment out the line by adding a '#' in front of it.";
			}
		}

		
		if ($errorMsg == "") {
			
			//all good
			echo "
				<center>
				<table border=1>
				<tr>
				<th width='800'>Successful Install</th>
				</tr>
				<tr>
				<td bgcolor='#66CD00'>
				Install Valid, Include this line when you need to use a shell:<br>
				require_once \"".$mtsInclude."\";</td>
				</tr>
				</table>
				</center>
				<br><br>
				";

			if ($warningMsg != "") {
				echo "
				<center>
				<table border=1>
				<tr>
				<th width='800'>Result: Warning</th>
				</tr>
				<tr>
				<td bgcolor='#FFFF00'>".$warningMsg."</td>
				</tr>
				</table>
				</center>
				";
			}
			
		} else {
			echo "
				<center>
				<table border=1>
				<tr>
				<th width='800'>Result: Error</th>
				</tr>
				<tr>
				<td bgcolor='#FF0000'>".$errorMsg."</td>
				</tr>
				</table>
				</center>
				";
		}

	
		//exit since php was installed
		exit;
		//end of web version
	}
?>

<!-- PHP not installed or module not loaded in webserver -->

<center>
<center>
	<table border=1>
	<tr>
	<th width='400'>Function</th>
	<th width='600'>Result</th>
	</tr>
	
	<tr>
	<td bgcolor='#FF0000'>PHP is not installed on your server</td>
	<td bgcolor='#FF0000'>
		<pre>
		If you are running a RedHat distribution you can issue the following commands in a shell ONE AT A TIME: 

		CentOS/RHEL 7:
		rpm -Uvh http://dl.fedoraproject.org/pub/epel/7/x86_64/e/epel-release-7-5.noarch.rpm
		rpm -Uvh http://rpms.famillecollet.com/enterprise/remi-release-7.rpm
		
		CentOS/RHEL 6:
		rpm -Uvh http://dl.fedoraproject.org/pub/epel/6/x86_64/epel-release-6-8.noarch.rpm
		rpm -Uvh http://rpms.famillecollet.com/enterprise/remi-release-6.rpm
		
		CentOS/RHEL 5:
		rpm -Uvh http://dl.fedoraproject.org/pub/epel/5/x86_64/epel-release-5-4.noarch.rpm
		rpm -Uvh http://rpms.famillecollet.com/enterprise/remi-release-5.rpm
		
		All CentOS/RHEL:
		yum install php55 php55-php --enablerepo=remi,epel

		If you are running a Debian distribution you can issue the following commands in a shell ONE AT A TIME: 

		Debian 8:
		apt-get install php5
		
		<!-- Ubuntu apache2 will send the raw php code to the client if the php mod is not loaded. cannot figure out how to make it stop doing that -->
		Ubuntu: You can issue the following commands in a shell ONE AT A TIME: 
		apt-get install libapache2-mod-php php

		</pre>
	</td>
	</tr>
	
	</table>
	</center>

</center>
