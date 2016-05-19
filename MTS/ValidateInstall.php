<?php
//© 2016 Martin Madsen
namespace MTS;

class ValidateInstall
{
	private $_serverOs=null;
	private $_paths=array();
	private $_localhostDevice=null;
	
    public function __construct()
    {	
	}
	public function validate()
	{
		$osName	= strtolower($this->getLocalServerOS()->getName());
		if ($osName == 'centos' || $osName == 'red hat enterprise') {
			return $this->validateRedhat();
		} elseif ($osName == 'debian' || $osName == 'ubuntu') {
			return $this->validateDebian();
		} else {
			$exceptionMsg	= "Expand to handle OS name:" . $this->getLocalServerOS()->getName();
			$this->throwException(__METHOD__ . ">> " . $exceptionMsg);
		}
	}
	private function validateDebian()
	{
		$return		= array();
		//is exec() allowed to run?
		if ($this->execEnabled() === true) {
			$return[]	= array(
					"success"	=> true,
					"function" 	=> "Exec Enabled",
					"msg"		=> "Success"
			);
		} else {
			$return[]	= array(
					"success"	=> false,
					"function" 	=> "Exec Enabled",
					"msg"		=> "Your host does not allow the exec() command. This is required."
			);
		}
	
		//is timezone set?
		if ($this->timezoneSet() === true) {
			$return[]	= array(
					"success"	=> true,
					"function" 	=> "Timezone set",
					"msg"		=> "Success"
			);
		} else {
				
			$iniLocation	= php_ini_loaded_file();
				
			$return[]	= array(
					"success"	=> false,
					"function" 	=> "Timezone set",
					"msg"		=> "find the line: ';date.timezone =' in ".$iniLocation.". Change it by removing the ';' in front and set the value to reflect your time zone. \n for Los Angeles that would be: 'date.timezone = America/Los_Angeles'"
			);
		}
	
		if ($this->execEnabled() === true) {
	
			//python install
			if ($this->pythonInstalled() === true) {
				$return[]	= array(
						"success"	=> true,
						"function" 	=> "Python Installed",
						"msg"		=> "Success"
				);
			} else {
				$return[]	= array(
						"success"	=> false,
						"function" 	=> "Python Installed",
						"msg"		=> "Run 'apt-get install python' on the command line of the server"
				);
			}
				
			//screen install
			if ($this->screenInstalled() === true) {
				$return[]	= array(
						"success"	=> true,
						"function" 	=> "Screen Installed",
						"msg"		=> "Success"
				);
			} else {
				$return[]	= array(
						"success"	=> false,
						"function" 	=> "Screen Installed",
						"msg"		=> "Run 'apt-get install screen' on the command line of the server"
				);
			}
				
			//can we write to the work directory
			if ($this->canWriteWorkDirectory() === true) {
				$return[]	= array(
						"success"	=> true,
						"function" 	=> "Work Directory Writable",
						"msg"		=> "Success"
				);
			} else {
				$return[]	= array(
						"success"	=> false,
						"function" 	=> "Work Directory Writable",
						"msg"		=> "Run 'chown -R ".$this->getWebserverUsername() .":".$this->getWebserverUsername() ." ".MTS_WORK_PATH."' on the command line of the server"
				);
			}
				
			//can we sudo a python execution
			if ($this->pythonInstalled() === true) {
	
				if ($this->sudoPython() === true) {
					$return[]	= array(
							"success"	=> true,
							"function" 	=> "Sudo Python",
							"msg"		=> "Success"
					);
				} else {
					$warnMsg	= array(
							"This is just a warning. You can still get a shell.",
							"To obtain a priviliged shell you must first construct an unpriviliged one 'getShell('bash', false)' and pass that shell with root credentials in the following method: ",
							"\MTS\Factories::getActions()->getRemoteUsers()->changeShellUser(\$shellObj, 'root', \$rootPass)",
							"If you want to use the second argument to gain priviliged access follow the steps below:",
							"",
							"1) A word of warning, giving Sudo access to the webserver user is a security risk.",
							"Edit /etc/sudoers in the following way:",
							"Find this line: 'root    ALL=(ALL)       ALL'",
							"Add this line after it: '".$this->getWebserverUsername() ." ALL=(ALL)NOPASSWD:".$this->getPythonExecutablePath()->getPathAsString()."'",
							"",
							"2) Find this line (if it exists): 'Defaults    requiretty'",
							"Comment out the line by adding a '#' in front of it.",
					);
	
					$return[]	= array(
							"success"	=> 'warn',
							"function" 	=> "Sudo Python",
							"msg"		=> implode("\n", $warnMsg)
					);
				}
			}
	
			if (
			$this->pythonInstalled() === true
			&& $this->screenInstalled() === true
			&& $this->canWriteWorkDirectory() === true
			&& $this->timezoneSet() === true
			) {
	
				if ($this->shellAvailable() === true) {
					$return[]	= array(
							"success"	=> true,
							"function" 	=> "Shell Creation Possible",
							"msg"		=> "Success"
					);
				} else {
					$failMsg	= array(
								
					);
	
					$return[]	= array(
							"success"	=> false,
							"function" 	=> "Shell Creation Possible",
							"msg"		=> implode("\n", $failMsg)
					);
				}
	
			}
	
		}
	
		return $return;
	}
	private function validateRedhat()
	{
		$return		= array();
		//is exec() allowed to run?
		if ($this->execEnabled() === true) {
			$return[]	= array(
					"success"	=> true,
					"function" 	=> "Exec Enabled",
					"msg"		=> "Success"
			);
		} else {
			$return[]	= array(
					"success"	=> false,
					"function" 	=> "Exec Enabled",
					"msg"		=> "Your host does not allow the exec() command. This is required."
			);
		}
		
		//is timezone set?
		if ($this->timezoneSet() === true) {
			$return[]	= array(
					"success"	=> true,
					"function" 	=> "Timezone set",
					"msg"		=> "Success"
			);
		} else {
			
			$iniLocation	= php_ini_loaded_file();
			
			$return[]	= array(
					"success"	=> false,
					"function" 	=> "Timezone set",
					"msg"		=> "find the line: ';date.timezone =' in ".$iniLocation.". Change it by removing the ';' in front and set the value to reflect your time zone. \n for Los Angeles that would be: 'date.timezone = America/Los_Angeles'"
			);
		}
		
		if ($this->execEnabled() === true) {
	
			//python install
			if ($this->pythonInstalled() === true) {
				$return[]	= array(
						"success"	=> true,
						"function" 	=> "Python Installed",
						"msg"		=> "Success"
				);
			} else {
				$return[]	= array(
						"success"	=> false,
						"function" 	=> "Python Installed",
						"msg"		=> "Run 'yum install python' on the command line of the server"
				);
			}
			
			//screen install
			if ($this->screenInstalled() === true) {
				$return[]	= array(
						"success"	=> true,
						"function" 	=> "Screen Installed",
						"msg"		=> "Success"
				);
			} else {
				$return[]	= array(
						"success"	=> false,
						"function" 	=> "Screen Installed",
						"msg"		=> "Run 'yum install screen' on the command line of the server"
				);
			}
			
			//can we write to the work directory
			if ($this->canWriteWorkDirectory() === true) {
				$return[]	= array(
						"success"	=> true,
						"function" 	=> "Work Directory Writable",
						"msg"		=> "Success"
				);
			} else {
				$return[]	= array(
						"success"	=> false,
						"function" 	=> "Work Directory Writable",
						"msg"		=> "Run 'chown -R ".$this->getWebserverUsername() .":".$this->getWebserverUsername() ." ".MTS_WORK_PATH."' on the command line of the server"
				);
			}
			
			//can we sudo a python execution
			if ($this->pythonInstalled() === true) {
	
				if ($this->sudoPython() === true) {
					$return[]	= array(
							"success"	=> true,
							"function" 	=> "Sudo Python",
							"msg"		=> "Success, You can use the second argument on 'getShell('bash', true)' to obtain priviliged access"
					);
				} else {
					$warnMsg	= array(
							"This is just a warning. You can still get a shell.",
							"To obtain a priviliged shell you must first construct an unpriviliged one 'getShell('bash', false)' and pass that shell with root credentials in the following method: ",
							"\MTS\Factories::getActions()->getRemoteUsers()->changeShellUser(\$shellObj, 'root', \$rootPass)",
							"If you want to use the second argument to gain priviliged access follow the steps below:",
							"",
							"1) A word of warning, giving Sudo access to the webserver user is a security risk.",
							"Edit /etc/sudoers in the following way:",
							"Find this line: 'root    ALL=(ALL)       ALL'",
							"Add this line after it: '".$this->getWebserverUsername() ." ALL=(ALL)NOPASSWD:".$this->getPythonExecutablePath()->getPathAsString()."'",
							"",
							"2) Find this line (if it exists): 'Defaults    requiretty'",
							"Comment out the line by adding a '#' in front of it.",
					);
	
					$return[]	= array(
							"success"	=> 'warn',
							"function" 	=> "Sudo Python",
							"msg"		=> implode("\n", $warnMsg)
					);
				}
			}
	
			if (
				$this->pythonInstalled() === true
				&& $this->screenInstalled() === true
				&& $this->canWriteWorkDirectory() === true
				&& $this->timezoneSet() === true
			) {
				
				if ($this->shellAvailable() === true) {
					$return[]	= array(
							"success"	=> true,
							"function" 	=> "Shell Creation Possible",
							"msg"		=> "Success"
					);
				} else {
					$failMsg	= array(
							
					);
				
					$return[]	= array(
							"success"	=> false,
							"function" 	=> "Shell Creation Possible",
							"msg"		=> implode("\n", $failMsg)
					);
				}
				
			}
		
		}

		return $return;
	}
	
	private function shellAvailable()
	{
		$localhost	= $this->getLocalHostDevice();
		$osType		= $this->getLocalServerOS()->getType();
		if ($osType == 'Linux') {
			
			try {
				$shellObj		= $localhost->getShell('bash', false);
				$username		= \MTS\Factories::getActions()->getRemoteOperatingSystem()->getUsername($shellObj);
				return true;
			} catch (\Exception $e) {
				switch($e->getCode()){
					default;
					return false;
				}
			}
		} else {
			$exceptionMsg	= "Expand to handle OS Type:" . $osType;
			$this->throwException(__METHOD__ . ">> " . $exceptionMsg);
		}
	}
	private function sudoPython()
	{
		return \MTS\Factories::getActions()->getLocalApplicationPaths()->getSudoEnabled('python');
	}
	private function getPythonExecutablePath()
	{
		if (array_key_exists('pythonExe', $this->_paths) === false) {
			
			$fileObj	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile('python');
				
			if ($fileObj !== false) {
				$this->_paths['pythonExe']	= $fileObj;
			} else {
				//python not installed
				return false;
			}
		}
		
		return $this->_paths['pythonExe'];
	}
	private function pythonInstalled()
	{
		$path	= $this->getPythonExecutablePath();
		if ($path === false) {
			return false;
		} else {
			return true;
		}
	}
	private function getScreenExecutablePath()
	{
		if (array_key_exists('screenExe', $this->_paths) === false) {
				
			$fileObj	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile('screen');
			
			if ($fileObj !== false) {
				$this->_paths['screenExe']	= $fileObj;
			} else {
				//screen not installed
				return false;
			}
		}
	
		return $this->_paths['screenExe'];
	}
	private function screenInstalled()
	{
		$path	= $this->getScreenExecutablePath();
		if ($path === false) {
			return false;
		} else {
			return true;
		}
	}
	private function getWebserverUsername()
	{
		return \MTS\Factories::getActions()->getLocalOperatingSystem()->getUsername();
	}
	private function getLocalServerOS()
	{
		if ($this->_serverOs === null) {
			$this->_serverOs	= \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
		}
	
		return $this->_serverOs;
	}
	private function throwException($msg)
	{
		//must make sure errors will surface
		error_reporting(E_ALL);
		ini_set('display_errors', true);
		throw new \Exception($msg);
	}

	private function execEnabled()
	{
		if (function_exists('exec') === false) {
			return false;
		} else {
			return true;
		}
	}
	private function localShellExec($cmdString)
	{
		exec($cmdString, $rData);
		$cReturn	= implode("\n", $rData);
		return $cReturn;
	}
	private function timezoneSet()
	{
		$timezone	= trim(ini_get('date.timezone'));
		if ($timezone == "") {
			return false;
		} else {
			return true;
		}
	}
	private function getLocalHostDevice()
	{
		if ($this->_localhostDevice === null) {
			$deviceFact					= \MTS\Factories::getDevices();
			$this->_localhostDevice		= $deviceFact->getLocalHost();
		}
	
		return $this->_localhostDevice;
	}
	private function canWriteWorkDirectory()
	{
		$rwWorkDir	= is_writable(MTS_WORK_PATH);
		if ($rwWorkDir === true) {
			return true;
		} else {
			return false;
		}
	}
}