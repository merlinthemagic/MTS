<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Types;
use \MTS\Common\Devices\Device;

class Localhost extends Device
{
	private $_classStore=array();
	public $debug=false;
	
	public function getBrowser($browserName='phantomjs')
	{
		return \MTS\Factories::getActions()->getLocalBrowser()->getBrowser($browserName, $this->debug);
	}
	
	public function getShell($shellType='bash', $asRoot=false)
	{
		//$asRoot
		//setting this to true will return a shell where commands are executed as root if sudo is available.
		//a false setting will return a shell where you execute as the php execution user, most likely apache or www-data
		
		//if you do not have sudo setup you can still get a root shell by running the unpriviliged shellObj through this function later:
		//\MTS\Factories::getActions()->getRemoteUsers()->changeShellUser($shell, 'root', 'rootPassword');

		return \MTS\Factories::getActions()->getLocalShell()->getShell($shellType, $asRoot, $this->debug);
	}
	public function getOS()
	{
		if (array_key_exists(__METHOD__, $this->_classStore) === false) {
			$this->_classStore[__METHOD__]	= $this->getAF()->getLocalOperatingSystem()->getOsObj();
		}
		return $this->_classStore[__METHOD__];
	}
}