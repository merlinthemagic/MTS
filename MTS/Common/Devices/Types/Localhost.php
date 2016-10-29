<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Types;
use \MTS\Common\Devices\Device;

class Localhost extends Device
{	
	public function getBrowser($browserName='default')
	{
		if ($browserName == 'default') {
			if ($this->getOS()->getType() == "Linux") {
				$browserName = 'phantomjs';
			} else {
				throw new \Exception("MTS browser does not support OS Type: " . $this->getOS()->getType());
			}
		}
		
		$browserName	= trim(strtolower($browserName));
		if (isset($this->_browserObjs[$browserName]) === false) {
			$this->_browserObjs[$browserName]	= \MTS\Factories::getActions()->getLocalBrowser()->getBrowser($browserName, $this->getDebug());
		}
		return $this->_browserObjs[$browserName];
	}
	
	public function getShell($shellName='default', $asRoot=false, $width=1000, $height=1000)
	{
		//$asRoot
		//setting this to true will return a shell where commands are executed as root if sudo is available.
		//a false setting will return a shell where you execute as the php execution user, most likely apache or www-data
		//you cannot change this after the shell has been returned, you would have to call a new instance of localhost to change
		
		//$width 
		//the terminal $COLUMNS count
		
		//$height
		//the terminal $LINES count
		
		//by default a very large terminal to avoid most terminal breaks
		
		//if you do not have sudo setup you can still get a root shell by running the unpriviliged shellObj through this function later:
		//\MTS\Factories::getActions()->getRemoteUsers()->changeShellUser($shellObj, 'root', 'rootPassword');
		
		if ($shellName == 'default') {
			if ($this->getOS()->getType() == "Linux") {
				$shellName = 'bash';
			} elseif ($this->getOS()->getType() == "Windows") {
				$shellName = 'powershell';
			} else {
				throw new \Exception("MTS shell does not support OS Type: " . $this->getOS()->getType());
			}
		}
		
		$shellName	= trim(strtolower($shellName));
		if (isset($this->_shellObjs[$shellName]) === false) {
			$this->_shellObjs[$shellName]		= \MTS\Factories::getActions()->getLocalShell()->getShell($shellName, $asRoot, $this->getDebug(), $width, $height);
		}
		return $this->_shellObjs[$shellName];
	}
	public function getOS()
	{
		//cached in action class
		return \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
	}
}