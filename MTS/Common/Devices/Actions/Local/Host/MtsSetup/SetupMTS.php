<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Local\Host\MtsSetup;

class SetupMTS
{
	public function getInstall()
	{
		$errorLevel	= null;
		$results	= array();

		//timezone
		if ($errorLevel != 'Error') {
			$result	= $this->timezoneValid();
			if ($result['errorLevel'] !== null) {
				$errorLevel	= $result['errorLevel'];
				$results[]	= $result;
			}
		}
		//exec allowed
		if ($errorLevel != 'Error') {
			$result	= $this->execEnabled();
			if ($result['errorLevel'] !== null) {
				$errorLevel	= $result['errorLevel'];
				$results[]	= $result;
			}
		}
		//workpath writable
		if ($errorLevel != 'Error') {
			$result	= $this->workpathWritable();
			if ($result['errorLevel'] !== null) {
				$errorLevel	= $result['errorLevel'];
				$results[]	= $result;
			}
		}
		//python Installed
		if ($errorLevel != 'Error') {
			$result	= $this->getPythonInstalled();
			if ($result['errorLevel'] !== null) {
				$errorLevel	= $result['errorLevel'];
				$results[]	= $result;
			}
		}
		//screen Installed
		if ($errorLevel != 'Error') {
			$result	= $this->getScreenInstalled();
			if ($result['errorLevel'] !== null) {
				$errorLevel	= $result['errorLevel'];
				$results[]	= $result;
			}
		}
		//phantomJS executable
		if ($errorLevel != 'Error') {
			$result	= $this->getPhantomJSExecutable();
			if ($result['errorLevel'] !== null) {
				$errorLevel	= $result['errorLevel'];
				$results[]	= $result;
			}
		}
		//font Config Installed
		if ($errorLevel != 'Error') {
			$result	= $this->getFontConfigInstalled();
			if ($result['errorLevel'] !== null) {
				$errorLevel	= $result['errorLevel'];
				$results[]	= $result;
			}
		}
		//font for PJS Installed
		if ($errorLevel != 'Error') {
			$result	= $this->getOptionalPJSInstalled();
			if ($result['errorLevel'] !== null) {
				$errorLevel	= $result['errorLevel'];
				$results[]	= $result;
			}
		}
		//sudo setup
		if ($errorLevel != 'Error') {
			$result	= $this->getSudoSetup();
			if ($result['errorLevel'] !== null) {
				$errorLevel	= $result['errorLevel'];
				$results[]	= $result;
			}
		}
		//create Shell
		if ($errorLevel != 'Error') {
			$result	= $this->createShell();
			if ($result['errorLevel'] !== null) {
				$errorLevel	= $result['errorLevel'];
				$results[]	= $result;
			}
		}
		//create Shell
		if ($errorLevel != 'Error') {
			$result	= $this->createBrowser();
			if ($result['errorLevel'] !== null) {
				$errorLevel	= $result['errorLevel'];
				$results[]	= $result;
			}
		}

		//finished
		if ($errorLevel != 'Error') {
			$result					= $this->getReturnArray();
			$result['msgHead']		= "Install Valid";
			$result['msgLines'][]	= "Include this line when you need to use a shell or browser:";
			$result['msgLines'][]	= "require_once \"".MTS_BASE_PATH . "MTS" . DIRECTORY_SEPARATOR . "EnableMTS.php\"";
			
			$results[]	= $result;
		}
		
		return $results;
	}
	
	
	
	
	
	
	
	//test functions
	public function timezoneValid()
	{
		$result		= $this->getReturnArray();
		$phpEnv		= \MTS\Factories::getActions()->getLocalPhpEnvironment();
	
		$timeZone	= $phpEnv->getIniTimezone();
		if ($timeZone === false) {
			$result['errorLevel']	= "Error";
			$result['msgHead']		= "PHP does not have its timezone set. This is required";
			$result['msgLines'][]	= "Find the line: ';date.timezone =' in ".$phpEnv->getIniFile()->getPathAsString()."";
			$result['msgLines'][]	= "Change it by removing the ';' in front and set the value to reflect your time zone.";
			$result['msgLines'][]	= "For Los Angeles that would be: 'date.timezone = America/Los_Angeles'";
		}
		return $result;
	}
	public function execEnabled()
	{
		$result		= $this->getReturnArray();
		$phpEnv		= \MTS\Factories::getActions()->getLocalPhpEnvironment();
	
		$execEnabled	= $phpEnv->getFunctionEnabled('exec');
		if ($execEnabled === false) {
			$result['errorLevel']	= "Error";
			$result['msgHead']		= "The PHP function exec() is not enabled. This is required";
			$result['msgLines'][]	= "Enable it to continue";
	
		}
		return $result;
	}
	public function workpathWritable()
	{
		$result		= $this->getReturnArray();
	
		$writable	= is_writable(MTS_WORK_PATH);
		if ($writable === false) {
			$result['errorLevel']	= "Error";
			$result['msgHead']		= "PHP cannot write to the MTS WorkDirectory. This is required";
			$result['msgLines'][]	= "Execute the following command to allow it:";
			$result['msgLines'][]	= "chown -R ".$this->getRunUser() .":".$this->getRunUser() ." ".MTS_WORK_PATH;
		} elseif (php_sapi_name() == "cli") {
			$result['errorLevel']	= "Info";
			$result['msgHead']		= "Make sure PHP can write to the MTS WorkDirectory. This is required";
			$result['msgLines'][]	= "Execute the following command to allow it:";
			$result['msgLines'][]	= "chown -R ".$this->getRunUser() .":".$this->getRunUser() ." ".MTS_WORK_PATH;
		}
		return $result;
	}
	public function getPythonInstalled()
	{
		$result		= $this->getReturnArray();
	
		$pythonExe	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile('python');
		if ($pythonExe === false) {
	
			$result['errorLevel']	= "Error";
			$result['msgHead']		= "Python is not installed. This is required";
			$result['msgLines'][]	= "Execute the following command to install:";
				
			$osObj		= \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
			$osName		= strtolower($osObj->getName());
			if ($osName == 'centos' || $osName == 'red hat enterprise') {
				$result['msgLines'][]	= "yum -y install python";
			} elseif ($osName == 'debian' || $osName == 'ubuntu') {
				$result['msgLines'][]	= "apt-get -y install python";
			} elseif ($osName == 'arch') {
				$result['msgLines'][]	= "pacman -S python";
			} else {
				throw new \Exception(__METHOD__ . ">> Expand to handle OS Name: " . $osName);
			}
		}
	
		return $result;
	}
	public function getScreenInstalled()
	{
		$result		= $this->getReturnArray();
	
		$screenExe	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile('screen');
		if ($screenExe === false) {
	
			$result['errorLevel']	= "Error";
			$result['msgHead']		= "Screen is not installed. This is required";
			$result['msgLines'][]	= "Execute the following command to install:";
	
			$osObj		= \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
			$osName		= strtolower($osObj->getName());
			if ($osName == 'centos' || $osName == 'red hat enterprise') {
				$result['msgLines'][]	= "yum -y install screen";
			} elseif ($osName == 'debian' || $osName == 'ubuntu') {
				$result['msgLines'][]	= "apt-get -y install screen";
			} elseif ($osName == 'arch') {
				$result['msgLines'][]	= "pacman -S screen";
			} else {
				throw new \Exception(__METHOD__ . ">> Expand to handle OS Name: " . $osName);
			}
		}
	
		return $result;
	}
	public function getPhantomJSExecutable()
	{
		$result		= $this->getReturnArray();
	
		$osObj		= \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
		$osType		= strtolower($osObj->getType());
		$osArch		= $osObj->getArchitecture();
	
		if ($osType == "linux" && $osArch == 64) {
			$pjsExe	= \MTS\Factories::getFiles()->getVendorFile("pjslinux64");
		} elseif ($osType == "linux" && $osArch == 32) {
			$pjsExe	= \MTS\Factories::getFiles()->getVendorFile("pjslinux32");
		} else {
			throw new \Exception(__METHOD__ . ">> Expand to handle OS Type: " . $osType . ", Architecture: " . $osArch);
		}
	
		$isPjsExec	= is_executable($pjsExe->getPathAsString());
		if ($isPjsExec === false) {
			$result['errorLevel']	= "Error";
			$result['msgHead']		= "PHP cannot execute PhantomJS binary. This is required";
			$result['msgLines'][]	= "Execute the following command to allow it:";
			$result['msgLines'][]	= "chmod +x " . $pjsExe->getPathAsString();
		}
	
		return $result;
	}
	public function getFontConfigInstalled()
	{
		$result		= $this->getReturnArray();
	
		$osObj		= \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
		$osName		= strtolower($osObj->getName());
		if ($osName == 'centos' || $osName == 'red hat enterprise') {
			$fontConfig	= trim($this->localShellExec("rpm -qa | grep -i \"fontconfig\""));
			if ($fontConfig == "") {
				$result['errorLevel']	= "Error";
				$result['msgHead']		= "Font Config is not installed. This is required";
				$result['msgLines'][]	= "Execute the following command to install:";
				$result['msgLines'][]	= "yum -y install fontconfig";
			}
		} elseif ($osName == 'debian' || $osName == 'ubuntu') {
			$fontConfig	= trim($this->localShellExec("dpkg-query -l \"fontconfig\""));
			if ($fontConfig == "") {
				$result['errorLevel']	= "Error";
				$result['msgHead']		= "Font Config is not installed. This is required";
				$result['msgLines'][]	= "Execute the following command to install:";
				$result['msgLines'][]	= "apt-get -y install fontconfig";
			}
		} elseif ($osName == 'arch') {
			$fontConfig	= trim($this->localShellExec("pacman -Qi \"fontconfig\""));
			if ($fontConfig == "") {
				$result['errorLevel']	= "Error";
				$result['msgHead']		= "Font Config is not installed. This is required";
				$result['msgLines'][]	= "Execute the following command to install:";
				$result['msgLines'][]	= "pacman -S fontconfig";
			}
				
		} else {
			throw new \Exception(__METHOD__ . ">> Expand to handle OS Name: " . $osName);
		}
	
		return $result;
	}
	public function getOptionalPJSInstalled()
	{
		$result		= $this->getReturnArray();
	
		$osObj		= \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
		$osName		= strtolower($osObj->getName());
		if ($osName == 'centos' || $osName == 'red hat enterprise') {
			$msttcoreFonts	= trim($this->localShellExec("rpm -qa | grep -i \"msttcore-fonts\""));
			if ($msttcoreFonts == "") {
				$result['errorLevel']	= "Warning";
				$result['msgHead']		= "MS core fonts is not installed. This is recommended";
				$result['msgLines'][]	= "Please Google 'msttcore-fonts' to find a source, then install ";
				$result['msgLines'][]	= "This may help: https://sourceforge.net/projects/mscorefonts2/files/rpms/";
			}
		} elseif ($osName == 'debian' || $osName == 'ubuntu') {
			$msttcoreFonts	= trim($this->localShellExec("dpkg-query -l \"ttf-mscorefonts-installer\""));
			if ($msttcoreFonts == "") {
				$result['errorLevel']	= "Warning";
				$result['msgHead']		= "MS core fonts is not installed. This is recommended";
				$result['msgLines'][]	= "The standard repos do not have this package";
				$result['msgLines'][]	= "Please Google 'ttf-mscorefonts-installer' to find a source, then install ";
				$result['msgLines'][]	= "This may help: http://serverfault.com/questions/89931/installing-msttcorefonts-on-ubuntu";
			}
		} elseif ($osName == 'arch') {
			$msttcoreFonts	= trim($this->localShellExec("pacman -Qi \"ttf-ms-fonts\""));
			if ($msttcoreFonts == "") {
				$result['errorLevel']	= "Warning";
				$result['msgHead']		= "MS core fonts is not installed. This is recommended";
				$result['msgLines'][]	= "The standard repos do not have this package";
				$result['msgLines'][]	= "Please Google 'ttf-ms-fonts' to find a source, then install ";
				$result['msgLines'][]	= "This may help: http://experimentswithlinuxrelatedtech.blogspot.pt/2014/04/how-to-quickly-install-packages-from.html";
			}
	
		} else {
			throw new \Exception(__METHOD__ . ">> Expand to handle OS Name: " . $osName);
		}
	
		return $result;
	}
	public function getSudoSetup()
	{
		$result		= $this->getReturnArray();
	
		$osObj		= \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
		$osName		= strtolower($osObj->getName());
	
		$sudoExe	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile('sudo');
		if ($sudoExe === false) {
				
			$result['errorLevel']	= "Info";
			$result['msgHead']		= "Sudo is not installed. This is optional";
			$result['msgLines'][]	= "Execute the following command to install:";
	
			$osObj		= \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
			$osName		= strtolower($osObj->getName());
			if ($osName == 'centos' || $osName == 'red hat enterprise') {
				$result['msgLines'][]	= "yum -y install sudo";
			} elseif ($osName == 'debian' || $osName == 'ubuntu') {
				$result['msgLines'][]	= "apt-get -y install sudo";
			} elseif ($osName == 'arch') {
				$result['msgLines'][]	= "pacman -S sudo";
			} else {
				throw new \Exception(__METHOD__ . ">> Expand to handle OS Name: " . $osName);
			}
		} else {
				
			//sudo installed, are we allowed to sudo python
			$sudoAllowed	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getSudoEnabled('python');
			if ($sudoAllowed === false) {
	
				$pythonExe	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile('python');
	
				$result['errorLevel']	= "Info";
				$result['msgHead']		= "The current user is not allowed to sudo python. This is optional";
				$result['msgLines'][]	= "You will still be able to instantiate non-root shells.";
				$result['msgLines'][]	= "You can then elevate the non-root shell with the following function:";
				$result['msgLines'][]	= "\MTS\Factories::getActions()->getRemoteUsers()->changeUser(\$shellObj, 'root', 'rootPassword')";
				$result['msgLines'][]	= "";
				$result['msgLines'][]	= "Warning: Giving Sudo access to the webserver user is a security risk.";
				$result['msgLines'][]	= "If you want to enable sudo for python follow the steps below";
				$result['msgLines'][]	= "Edit /etc/sudoers:";
				$result['msgLines'][]	= "1) Find this line: 'root    ALL=(ALL)       ALL'";
				$result['msgLines'][]	= "Add this line after it: '".$this->getRunUser()." ALL=(ALL)NOPASSWD:".$pythonExe->getPathAsString()."'.";
				$result['msgLines'][]	= "";
				$result['msgLines'][]	= "2) Find this line (if it exists): 'Defaults    requiretty'";
				$result['msgLines'][]	= "Comment out the line by adding a '#' in front of it.";
			}
		}
	
		return $result;
	}
	public function createShell()
	{
		$result		= $this->getReturnArray();
	
		try {
			
			$shellObj		= \MTS\Factories::getDevices()->getLocalHost()->getShell();
			$username		= \MTS\Factories::getActions()->getRemoteUsers()->getUsername($shellObj);

		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				$result['errorLevel']	= "Error";
				$result['msgHead']		= "Unable to create shell";
				$result['msgLines'][]	= "Exception Message: " . $e->getMessage();
			}
		}

		return $result;
	}
	public function createBrowser()
	{
		$result		= $this->getReturnArray();
	
		try {
			$browserObj		= \MTS\Factories::getDevices()->getLocalHost()->getBrowser('phantomjs');
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				$result['errorLevel']	= "Error";
				$result['msgHead']		= "Unable to create browser";
				$result['msgLines'][]	= "Exception Message: " . $e->getMessage();
			}
		}
		return $result;
	}
	
	public function getRunUser()
	{
		if (php_sapi_name() == "cli") {
			return '$userName';
		} else {
			return \MTS\Factories::getActions()->getLocalUsers()->getUsername();
		}
	}
	protected function getReturnArray()
	{
		$result					= array();
		$result['errorLevel']	= null;
		$result['msgHead']		= "";
		$result['msgLines']		= array();
	
		return $result;
	}
	protected function localShellExec($cmdString)
	{
		exec($cmdString, $rData);
		$cReturn	= implode("\n", $rData);
		return $cReturn;
	}
}