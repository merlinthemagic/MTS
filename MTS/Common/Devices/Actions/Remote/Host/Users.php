<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Remote\Host;
use MTS\Common\Devices\Actions\Remote\Base;

class Users extends Base
{
	public function getShellUsername($shellObj)
	{
		//return the name of the user php is executed as
		$this->_classStore['requestType']	= __FUNCTION__;
		$this->_classStore['shellObj']		= $shellObj;
		return $this->execute();
	}
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
		$shellObj			= $this->_classStore['shellObj']->getActiveShell();
		
		if ($requestType == 'changeShellUser') {

			$currentUser	= $this->getShellUsername($shellObj);
			$username		= $this->_classStore['username'];
			$password		= $this->_classStore['password'];
			
			//remove the username and password, they are too sensetive to keep around in case the object is dumped
			unset($this->_classStore['username']);
			unset($this->_classStore['password']);
			
			if (strtolower($currentUser) != strtolower($username)) {
				$childShell		= null;
				
				if ($shellObj instanceof \MTS\Common\Devices\Shells\Bash) {

					$regExSu	= "(Password:|user ".$username." does not exist|This account is currently not available|".$username."@)";
					$suReturn	= $shellObj->exeCmd("su " . $username, $regExSu);
					preg_match("/".$regExSu."/", $suReturn, $returnSu);
					
					if (isset($returnSu[1]) === true) {
						if ($returnSu[1] == "Password:") {
						
							if ($password === null) {
								//exit the password prompt
								$shellObj->killLastProcess();
								throw new \Exception(__METHOD__ . ">> User: " . $username . ", requires a password, but none provided, cannot change shell user");
							} else {
									
								$regExPass	= "(".$username."@|Authentication failure)";
								$passReturn	= $shellObj->exeCmd($password, $regExPass);
								preg_match("/".$regExPass."/", $passReturn, $returnPass);
									
								if (isset($returnPass[1]) === true) {
									if ($returnPass[1] == $username."@") {
										//logged in
										$childShell			= new \MTS\Common\Devices\Shells\Bash();
									} elseif ($returnPass[1] == "Authentication failure") {
										$shellObj->killLastProcess();
										throw new \Exception(__METHOD__ . ">> User: " . $username . ", incorrect password");
									}
								}
							}
						
						} elseif ($returnSu[1] == "user ".$username." does not exist") {
							$shellObj->killLastProcess();
							throw new \Exception(__METHOD__ . ">> User: " . $username . ", does not exist, cannot change shell user");
						} elseif ($returnSu[1] == "This account is currently not available") {
							$shellObj->killLastProcess();
							throw new \Exception(__METHOD__ . ">> User: " . $username . ", account not available, cannot change shell user");
						} elseif ($returnSu[1] == $username."@") {
							//was able to login without password
							$childShell			= new \MTS\Common\Devices\Shells\Bash();
						}
					}
				}
				

				if ($childShell !== null) {
				
					$shellObj->setChildShell($childShell);
					$newUser	= $this->getShellUsername($childShell);

					if (strtolower($username) == strtolower($newUser)) {
						//user was successfully changed
						return $childShell;
					} else {
						//wrong user, get out
						$shellObj->exeCmd("exit", false, 100);
						$shellObj->exeCmd("");
						throw new \Exception(__METHOD__ . ">> Error: Changing user to: ".$username.", got logged in as: " . $newUser);
					}
				}
				
			} else {
				//already logged in as that user
				return $shellObj;
			}
			
		} elseif ($requestType == 'getShellUsername') {
			if ($shellObj instanceof \MTS\Common\Devices\Shells\Bash) {
				$username			= trim($shellObj->exeCmd("whoami"));
				if (strlen($username) > 0) {
					return $username;
				}
			} elseif ($shellObj instanceof \MTS\Common\Devices\Shells\RouterOS) {
				
				$reData		= $shellObj->exeCmd("", "\[(.*?)\>");
				if (preg_match("/\[(.*?)\@(.*?)\]\s\>/", $reData, $rawUser) == 1) {
					return trim($rawUser[1]);
				}
			}
		}

		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}