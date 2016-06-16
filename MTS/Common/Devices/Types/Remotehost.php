<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Types;
use \MTS\Common\Devices\Device;

class Remotehost extends Device
{
	protected $_hostname=null;
	
	protected $_shellUsername=null;
	protected $_shellPassword=null;
	protected $_shellType=null;
	protected $_shellPort=null;
	
	public function setHostname($hostname)
	{
		//fqdn or ip
		$this->_hostname	= $hostname;
		return $this;
	}
	public function getHostname()
	{
		return $this->_hostname;
	}
	public function setConnectionDetail($username, $password, $type=null, $port=null)
	{
		$this->_shellUsername	= $username;
		$this->_shellPassword	= $password;
	
		if ($type === null) {
			$this->_shellType		= 'ssh';
		} else {
			$this->_shellType		= strtolower($type);
		}
		if ($port === null) {
			$this->_shellPort		= 22;
		} else {
			$this->_shellPort		= intval($port);
		}
		return $this;
	}
	
	public function getShell($shellObj=null)
	{
		try {
			if ($this->_shellObj === null) {

				//dont keep sensetive information around in case the class is dumped
				$username				= $this->_shellUsername;
				$password				= $this->_shellPassword;
				$this->_shellUsername	= null;
				$this->_shellPassword	= null;
				
				if ($this->getHostname() !== null) {
					if ($username !== null ) {
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
						
						if (strtolower(get_class($this)) == 'mts\common\devices\types\mikrotik') {
							//its faster to apply default terminal options to the username
							//this is a ROS device (only available DeviceCtrl package is installed)
							$termOptions	= \MTS\Factories::getActions()->getRemoteConnectionsSsh()->getMtTermOptions();
							preg_match("/(.*?)\+(.*)/", $username, $addName);
							if (isset($addName[2]) === false) {
								//username does not include any options
								$username	= $username . "+" . $termOptions;
							} else if ($addName[2] != $termOptions) {
								//username has the wrong options
								$username	= $addName[1] . "+" . $termOptions;
							}
						}

						if ($this->_shellType == 'ssh') {
							//and replace it with the new one
							$this->_shellObj		= \MTS\Factories::getActions()->getRemoteConnectionsSsh()->connectByUsername($shellObj, $username, $password, $this->getHostname(), $this->_shellPort);
						} else {
							throw new \Exception(__METHOD__ . ">> Not Handled for Type: " . $this->_mtShellType);
						}
				
					} else {
						//expand to allow public key login
						throw new \Exception(__METHOD__ . ">> Missing Connection details");
					}
				} else {
					throw new \Exception(__METHOD__ . ">> Missing Hostname");
				}
			}
			return $this->_shellObj;
		
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
}