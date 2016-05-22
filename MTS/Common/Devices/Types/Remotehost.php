<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Types;
use \MTS\Common\Devices\Device;

class Remotehost extends Device
{
	private $_hostname=null;
	
	public function setHostname($hostname)
	{
		//fqdn or ip
		$this->_hostname	= $hostname;
	}
	public function getHostname()
	{
		return $this->_hostname;
	}
	public function getShellBySsh($username, $password, $port=22, $shellObj=null)
	{
		if ($this->getHostname() === null) {
			throw new \Exception(__METHOD__ . ">> You must set a hostname before making ssh connections");
		}
		
		if ($shellObj === null) {
			//will build a new ssh connection from a local shell
			$sudoEnabled	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getSudoEnabled('python');
			if ($sudoEnabled === true) {
				//root is more likely to have SSH privilige
				$shellObj	= \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', true);
			} else {
				$shellObj	= \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', false);
			}
			
		} else {
			//you have already connected to another host and want to make the connection from that host
		}
		\MTS\Factories::getActions()->getRemoteConnectionsSsh()->connectByUsername($shellObj, $username, $password, $this->getHostname(), $port);
		
		return $shellObj;
	}
}