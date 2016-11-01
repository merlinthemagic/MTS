<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Remote\Host;
use MTS\Common\Devices\Actions\Remote\Base;

class Users extends Base
{
	public function getUsername($shellObj)
	{
		$this->_classStore['requestType']	= __FUNCTION__;
		$this->_classStore['shellObj']		= $shellObj;
		return $this->execute();
	}
	public function changeUser($shellObj, $username, $password=null)
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
		$osObj				= \MTS\Factories::getActions()->getRemoteOperatingSystem()->getOsObj($shellObj);
		
		if ($requestType == 'changeUser') {

			//remove the username and password for object vars
			//they are too sensetive to keep around in case the object is dumped
			$username		= $this->_classStore['username'];
			$password		= $this->_classStore['password'];
			unset($this->_classStore['username']);
			unset($this->_classStore['password']);
			
			$currentUser	= $this->getUsername($shellObj);
			if (strtolower($currentUser) != strtolower($username)) {
				
				$childShell		= null;
				if ($osObj->getType() == "Linux") {

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
					
					if ($childShell !== null) {
					
						$shellObj->setChildShell($childShell);
						$newUser	= $this->getUsername($childShell);
						if (strtolower($username) == strtolower($newUser)) {
							//user was successfully changed
							return $childShell;
						} else {
							//wrong user, get out
							$childShell->terminate();
							throw new \Exception(__METHOD__ . ">> Error: Changing user to: ".$username.", got logged in as: " . $newUser);
						}
					}
					
				} elseif ($osObj->getType() == "Windows") {

					$pShellObj	= $shellObj->getParentShell();
					if ($pShellObj === null) {
						
						$powerShellExe	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile('powershell');
						$fileFact		= \MTS\Factories::getFiles();
						$pipeUuid		= uniqid();
						$workPath		= $fileFact->getDirectory(MTS_WORK_PATH . DIRECTORY_SEPARATOR . "LHS_" . $pipeUuid);
							
						$stdIn			= $fileFact->getFile("stdIn", $workPath->getPathAsString());
						$stdOut			= $fileFact->getFile("stdOut", $workPath->getPathAsString());
						$stdErr			= $fileFact->getFile("stdErr", $workPath->getPathAsString());
						
						$psInit			= $fileFact->getVendorFile("psv1ctrl");

						$fileFact->getFilesTool()->create($stdIn);
						$fileFact->getFilesTool()->create($stdOut);
						$fileFact->getFilesTool()->create($stdErr);
				
						//the files are not cleaned up after exit
						//$changeCmd		= "Start-Process -FilePath \"".$powerShellExe->getPathAsString()."\" -Credential \$mtsChangeCred -LoadUserProfile -ArgumentList \"-executionPolicy Unrestricted\", '\"" . $psInit->getPathAsString() . "\" \"".$workPath->getPathAsString()."\"'";
						//$changeCmd		= "Start-Process -FilePath \"".$powerShellExe->getPathAsString()."\" -ArgumentList \"-executionPolicy Unrestricted\", '\"" . $psInit->getPathAsString() . "\" \"".$workPath->getPathAsString()."\"'";						
						
						$credCmd		= "new-object -typename System.Management.Automation.PSCredential -argumentlist '".$username."', $(ConvertTo-SecureString '".$password."' -AsPlainText -Force)";
						$argList		= "\"-executionPolicy Unrestricted\", '\"" . $psInit->getPathAsString() . "\" \"".$workPath->getPathAsString()."\"'";
						
						//remove -Wait whe you get this working on IIS
						$changeCmd		= "Start-Process -Wait -FilePath \"".$powerShellExe->getPathAsString()."\" -Credential $(".$credCmd.") -ArgumentList (".$argList.")";
						$cReturn		= trim($shellObj->exeCmd($changeCmd));

						if (preg_match("/(logon failure|access is denied)/i", $cReturn, $match) == 1) {
							//clean up and exit
							$fileFact->getFilesTool()->delete($stdIn);
							$fileFact->getFilesTool()->delete($stdOut);
							$fileFact->getFilesTool()->delete($stdErr);
							$fileFact->getDirectoriesTool()->delete($workPath);
							
							$reason	= trim(strtolower($match[1]));
							
							if ($reason == "access is denied") {
								throw new \Exception(__METHOD__ . ">> Server does not allow change of user");
							} else {
								throw new \Exception(__METHOD__ . ">> Invalid credentials");
							}

						} else {
							
							$newUser	= $this->getUsername($childShell);
							if (strtolower($username) == strtolower($newUser)) {
								//all good shell was created
								$stdPipe	= $fileFact->getProcessPipe($stdIn, $stdOut, $stdErr);
								$childShell	= new \MTS\Common\Devices\Shells\PowerShell();
								$childShell->setPipes($stdPipe);
								$shellObj->setChildShell($childShell);
								
								//user was successfully changed
								return $childShell;
								
							} else {
								
								$fileFact->getFilesTool()->delete($stdIn);
								$fileFact->getFilesTool()->delete($stdOut);
								$fileFact->getFilesTool()->delete($stdErr);
								$fileFact->getDirectoriesTool()->delete($workPath);
								
								throw new \Exception(__METHOD__ . ">> Error: Changing user to: ".$username.", got logged in as: " . $newUser . "");
							}
						}
						
					} else {
						throw new \Exception(__METHOD__ . ">> Not Handled for Remote Powershells yet");
					}
				}
				
			} else {
				//already logged in as that user
				return $shellObj;
			}
			
		} elseif ($requestType == 'getUsername') {

			if ($osObj->getType() == "Linux") {
				$username			= trim($shellObj->exeCmd("whoami"));
				if (strlen($username) > 0) {
					return $username;
				}
			} elseif ($osObj->getType() == "Mikrotik") {
				
				$reData		= $shellObj->exeCmd("", "\[(.*?)\>");
				if (preg_match("/\[(.*?)\@(.*?)\]\s\>/", $reData, $rawUser) == 1) {
					return trim($rawUser[1]);
				}
			} elseif ($osObj->getType() == "Windows") {
				$username			= trim($shellObj->exeCmd("whoami"));
				if (strlen($username) > 0) {
					return $username;
				}
			}
		}

		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}