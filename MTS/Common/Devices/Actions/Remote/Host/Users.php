<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Remote\Host;
use MTS\Common\Devices\Actions\Remote\Base;

class Users extends Base
{
	public function changeShellUser($shellObj, $username, $password=null)
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
			
			$success		= true;
			$currentUser	= \MTS\Factories::getActions()->getRemoteOperatingSystem()->getUsername($shellObj);
			$username		= $this->_classStore['username'];
			$password		= $this->_classStore['password'];
			
			//remove the username and password, they are too sensetive to keep around in case the object is dumped
			unset($this->_classStore['username']);
			unset($this->_classStore['password']);
			
			if (strtolower($currentUser) != strtolower($username)) {
				if ($shellObj instanceof \MTS\Common\Devices\Shells\Bash) {

					$regExSu	= "(Password:|user ".$username." does not exist|This account is currently not available|".$username."@)";
					$suReturn	= $shellObj->exeCmd("su " . $username, $regExSu);
					preg_match("/".$regExSu."/", $suReturn, $returnSu);
					
					if (!isset($returnSu[1])) {
						//let this pass through it is not handled
						$success	= false;
					} elseif ($returnSu[1] == "Password:") {
						
						if ($password === null) {
							//exit the password prompt
							$shellObj->killLastProcess();
							throw new \Exception(__METHOD__ . ">> User: " . $username . ", requires a password, but none provided, cannot change shell user");
						} else {
							
							$regExPass	= "(".$username."|Authentication failure)";
							$passReturn	= $shellObj->exeCmd($password, $regExPass);
							preg_match("/".$regExPass."/", $passReturn, $returnPass);
							
							if (!isset($returnPass[1])) {
								//let this pass through it is not handled
								$success	= false;
							} elseif ($returnPass[1] == "Authentication failure") {
								throw new \Exception(__METHOD__ . ">> User: " . $username . ", incorrect password");
							}
						}
						
					} elseif ($returnSu[1] == "user ".$username." does not exist") {
						throw new \Exception(__METHOD__ . ">> User: " . $username . ", does not exist, cannot change shell user");
					} elseif ($returnSu[1] == "This account is currently not available") {
						throw new \Exception(__METHOD__ . ">> User: " . $username . ", account not available, cannot change shell user");
					} elseif ($returnSu[1] == $username."@") {
						//was able to login without password
					}

					if ($success === true) {
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
		}

		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}