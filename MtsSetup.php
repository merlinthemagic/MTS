<!-- © 2016 Martin Madsen  -->
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

<?php

	//fill the mtsBasePath input box again
	if (array_key_exists('mtsBasePath', $_POST) === true && strlen($_POST['mtsBasePath']) > 0) {
		$mtsBasePath	= trim($_POST['mtsBasePath']);
		$mtsBasePath	= rtrim($mtsBasePath, DIRECTORY_SEPARATOR);

		echo "<script>document.getElementById('mtsBasePath').value = '".$mtsBasePath."';</script>";
	} else {
		echo "<br><center><font color='#FF0000'><h2>You must specify a path to the MTS files</h2></center><br>";
		//exit we must have a base path
		exit;
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
	
	$vClass		= new \MTS\ValidateInstall();
	$vResult	= $vClass->validate();
	
	$valid	= true;
	$rTable	= "";
	$rTable	.= "
			<center>
			<table border=1>
			<tr>
			<th width='400'>Function</th>
			<th width='600'>Result</th>
			</tr>
			";
	
	foreach ($vResult as $result) {
		
		if ($result['success'] === true) {
			$rTable	.= "
			<tr>
			<td bgcolor='#66CD00'>".$result['function']."</td>
			<td bgcolor='#66CD00'>".str_replace("\n", "<br>", $result['msg'])."</td>
			</tr>
			";
		} else {
			$valid	= false;
			$rTable	.= "
			<tr>
			<td bgcolor='#FF0000'>".$result['function']."</td>
			<td bgcolor='#FF0000'>".str_replace("\n", "<br>", $result['msg'])."</td>
			</tr>
			";
		}
	}

	$rTable	.= "
			</table>
			</center>
			";
	
	if ($valid === true) {
		//install is acceptable
		echo "<br><center><font color='#006400'><h2>Install Valid, you can proceed</h2></center>";
		echo "<pre>
				Include this line when you need to use the tools: 'require_once \"".$mtsInclude."\";'
				
				Here is a test showing you how to use the shell:
					
				\$shell		= \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', true);
				\$return1	= \$shell->exeCmd('whoami');
				
				echo \$return1; //root
					
				Another test:
					
				\$shell		= \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', true);
				\$return1	= \$shell->exeCmd('cd /etc/sysconfig');
				\$return2	= \$shell->exeCmd('ls -sho --color=none');
				
				echo \$return2; //list of files in /etc/sysconfig
					
				</pre>";
		
	} else {
		//install is fundementally flawed and cannot proceed
		echo "<br><center><font color='#FF0000'><h2>Install Invalid, you cannot proceed</h2></center><br>";
	}
	
	echo $rTable;

	//exit since php was installed
	exit;
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
