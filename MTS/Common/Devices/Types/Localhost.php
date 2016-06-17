<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Types;
use \MTS\Common\Devices\Device;

class Localhost extends Device
{	
	public function getBrowser($browserName='phantomjs')
	{
		if ($this->_browserObj === null) {
			$this->_browserObj	= \MTS\Factories::getActions()->getLocalBrowser()->getBrowser($browserName, $this->getDebug());
		}
		return $this->_browserObj;
	}
	
	public function getShell($shellType='bash', $asRoot=false)
	{
		//$asRoot
		//setting this to true will return a shell where commands are executed as root if sudo is available.
		//a false setting will return a shell where you execute as the php execution user, most likely apache or www-data
		//you cannot change this after the shell has been returned, you would have to call a new instance of localhost to change
		
		//if you do not have sudo setup you can still get a root shell by running the unpriviliged shellObj through this function later:
		//\MTS\Factories::getActions()->getRemoteUsers()->changeShellUser($shell, 'root', 'rootPassword');
		if ($this->_shellObj === null) {
			$this->_shellObj		= \MTS\Factories::getActions()->getLocalShell()->getShell($shellType, $asRoot, $this->getDebug());
		}
		return $this->_shellObj;
	}
	public function getOS()
	{
		if (array_key_exists(__METHOD__, $this->_classStore) === false) {
			$this->_classStore[__METHOD__]	= \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
		}
		return $this->_classStore[__METHOD__];
	}
}