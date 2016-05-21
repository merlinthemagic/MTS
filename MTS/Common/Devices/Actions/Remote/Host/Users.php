<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Remote\Host;
use MTS\Common\Devices\Actions\Remote\Base;

class Users extends Base
{
	public function changeShellUser($shellObj, $username, $password)
	{
		//return the name of the user php is executed as
		$this->_classStore['requestType']	= __FUNCTION__;
		$this->_classStore['shellObj']		= $shellObj;
		$this->_classStore['username']		= $username;
		$this->_classStore['password']		= $password;
		return $this->execute();
	}
	private function execute()
	{
		$requestType		= $this->_classStore['requestType'];
		$shellObj			= $this->_classStore['shellObj'];
		
		if ($requestType == 'changeShellUser') {
			
			$currentUser	= \MTS\Factories::getActions()->getRemoteOperatingSystem()->getUsername($shellObj);
			$username		= $this->_classStore['username'];
			$password		= $this->_classStore['password'];
			
			if (strtolower($currentUser) != strtolower($username)) {
				if ($shellObj instanceof \MTS\Common\Devices\Shells\Bash) {
					$suReturn			= $shellObj->exeCmd("su " . $username, "Password:");
					$loginReturn		= $shellObj->exeCmd($password, $username);
					
					//remove the username and password, they are too sensetive to keep around in case the object is dumped
					unset($this->_classStore['username']);
					unset($this->_classStore['password']);
					
					$childShell			= new \MTS\Common\Devices\Shells\Bash();
					$childShell->setParentShell($shellObj);
					$shellObj->setChildShell($childShell);
					
					//we must issue at least one command to initialize the new shell, because it is already running
					$newUser	= \MTS\Factories::getActions()->getRemoteOperatingSystem()->getUsername($shellObj);
					
					if (strtolower($username) == strtolower($newUser)) {
						//user was successfully changed
						return;
					}
				}
			}
		}

		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}