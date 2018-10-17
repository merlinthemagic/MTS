<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Remote\Connections;
use MTS\Common\Devices\Actions\Remote\Base;

class Ssh extends Base
{
	public function connectByUsername($shellObj, $username, $password, $ipaddress, $port=22, $timeout=30000)
	{
		//return the name of the user php is executed as
		$this->_classStore['requestType']	= __FUNCTION__;
		$this->_classStore['shellObj']		= $shellObj;
		$this->_classStore['username']		= $username;
		$this->_classStore['password']		= $password;
		$this->_classStore['ipaddress']		= $ipaddress;
		$this->_classStore['port']			= $port;
		$this->_classStore['timeout']		= $timeout;
		return $this->execute();
	}
	public function getMtTermOptions()
	{
		//default terminal options for all Mikrotik SSH connections. We need the terminal without colors and a standard width
		//picked 300 because putty in a 1920x1080 window maximized is 237 columns
		//the when building on a shell the parent must support at least 300 wide
		return "ct300w";
	}
	
	private function execute()
	{
		$requestType		= $this->_classStore['requestType'];
		$shellObj			= $this->_classStore['shellObj']->getActiveShell();
		$osObj				= \MTS\Factories::getActions()->getRemoteOperatingSystem()->getOsObj($shellObj);
		
		if ($requestType == 'connectByUsername') {
			
			$ipaddress		= $this->_classStore['ipaddress'];
			$username		= $this->_classStore['username'];
			$password		= $this->_classStore['password'];
			$port			= $this->_classStore['port'];
			$timeout		= $this->_classStore['timeout'];
			
			//remove the username and password, they are too sensetive to keep around in case the object is dumped
			unset($this->_classStore['username']);
			unset($this->_classStore['password']);

			if ($osObj->getType() == "Linux") {
				return $this->connectByUsernameFromLinux($shellObj, $username, $password, $ipaddress, $port, $timeout);
			} elseif ($osObj->getType() == "Mikrotik") {
				return $this->connectByUsernameFromMikrotik($shellObj, $username, $password, $ipaddress, $port, $timeout);
			}
		}

		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
	
	private function connectByUsernameFromLinux($shellObj, $username, $password, $ipaddress, $port, $timeout)
	{
		$requestType	= $this->_classStore['requestType'];
		$osObj			= \MTS\Factories::getActions()->getRemoteOperatingSystem()->getOsObj($shellObj);

		if ($osObj->getType() == "Linux") {
		    
		    if ($username === null) {
		        $username		= \MTS\Factories::getActions()->getRemoteUsers()->getUsername($shellObj);
		    }

			$returnConn	= null;
			try {
				$connCmd		= "ssh -p ".$port." -o \"StrictHostKeyChecking no\" -o \"GSSAPIAuthentication=no\" ".$username."@".$ipaddress."";
				$regExConn		= "(".$ipaddress."'s password:|No route to host|Could not resolve hostname|".$username."@)";
				$connReturn		= $shellObj->exeCmd($connCmd, $regExConn, $timeout);
				if (preg_match("/".$regExConn."/", $connReturn, $returnConn) == 1) {
					$returnConn	= $returnConn[1];
				}
					
			} catch (\Exception $e) {
				switch($e->getCode()){
					case 2500:
						//cleanup then throw
						$shellObj->killLastProcess();
						throw new \Exception(__METHOD__ . ">> Connection to: " . $ipaddress . ", timeout");
						break;
					default;
					throw $e;
				}
			}
			
			if ($returnConn == $ipaddress."'s password:") {
					
				$returnPass	= null;
				try {
					
					$regExPass	= "(MikroTik RouterOS|Permission denied|".$username."@)";
					$passReturn	= $shellObj->exeCmd($password, $regExPass);
					if (preg_match("/".$regExPass."/", $passReturn, $returnPass) == 1) {
						$returnPass	= $returnPass[1];
					}

				} catch (\Exception $e) {
					switch($e->getCode()){
						case 2500:
							//cleanup then throw
							$shellObj->killLastProcess();
							throw new \Exception(__METHOD__ . ">> Connection to: " . $ipaddress . ", password timeout");
							break;
						default;
						throw $e;
					}
				}
					
				if ($returnPass == "MikroTik RouterOS") {
					return $this->connectByUsernameToMikrotik($shellObj, $username, $password, $ipaddress, $port);
				} elseif ($returnPass == $username."@") {
					return $this->connectByUsernameToLinux($shellObj, $username, $password, $ipaddress, $port);
				} elseif ($returnPass == "Permission denied") {
					throw new \Exception(__METHOD__ . ">> Invaild Credentials");
				}
					
			} elseif ($returnConn == "No route to host") {
				throw new \Exception(__METHOD__ . ">> SSH: No route to host");
			} elseif ($returnConn == "Could not resolve hostname") {
				throw new \Exception(__METHOD__ . ">> SSH: Could not resolve hostname: " . $ipaddress);
			}
		} elseif ($returnPass == $username."@") {
		    //public key auth
		    return $this->connectByUsernameToLinux($shellObj, $username, $password, $ipaddress, $port);
		}
		
		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
	
	private function connectByUsernameFromMikrotik($shellObj, $username, $password, $ipaddress, $port, $timeout)
	{
		$requestType	= $this->_classStore['requestType'];
		$osObj			= \MTS\Factories::getActions()->getRemoteOperatingSystem()->getOsObj($shellObj);
		
		if ($osObj->getType() == "Mikrotik") {
		
			$returnConn	= null;
			try {
				$connCmd		= "/system ssh address=\"".$ipaddress."\" port=".$port." user=\"".$username."\"";
			
				$regExConn		= "(password:|Connection timed out|No route to host)";
				$connReturn		= $shellObj->exeCmd($connCmd, $regExConn, $timeout);

				if (preg_match("/".$regExConn."/", $connReturn, $returnConn) == 1) {
					$returnConn	= $returnConn[1];
				}
				
			
			} catch (\Exception $e) {
				switch($e->getCode()){
					case 2500:
						//cleanup then throw
						$shellObj->killLastProcess();
						throw new \Exception(__METHOD__ . ">> Connection to: " . $ipaddress . ", timeout");
						break;
					default;
					throw $e;
				}
			}
			
			if ($returnConn == "password:") {
				
				$returnPass	= null;
				try {
					
					$regExPass	= "(MikroTik RouterOS|password:|".$username."@)";
					$passReturn	= $shellObj->exeCmd($password, $regExPass);
					if (preg_match("/".$regExPass."/", $passReturn, $returnPass) == 1) {
						$returnPass	= $returnPass[1];
					}
					
				} catch (\Exception $e) {
					switch($e->getCode()){
						case 2500:
							//cleanup then throw
							$shellObj->killLastProcess();
							throw new \Exception(__METHOD__ . ">> Connection to: " . $ipaddress . ", password timeout");
							break;
						default;
						throw $e;
					}
				}
					
				if ($returnPass == "MikroTik RouterOS") {
					return $this->connectByUsernameToMikrotik($shellObj, $username, $password, $ipaddress, $port);
				} elseif ($returnPass == $username."@") {
					return $this->connectByUsernameToLinux($shellObj, $username, $password, $ipaddress, $port);
				} elseif ($returnPass == "password:") {
					$shellObj->killLastProcess();
					throw new \Exception(__METHOD__ . ">> User: " . $username . ", incorrect password");
				}
				
			} elseif ($returnConn == "No route to host") {
				throw new \Exception(__METHOD__ . ">> SSH: No route to host");
			}
		}
		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
	
	private function connectByUsernameToLinux($shellObj, $username, $password, $ipaddress, $port)
	{
		$requestType		= $this->_classStore['requestType'];
		
		//logged in, now figure out what type of shell we got on the other side
		$strCmd			= "ps hp $$ | awk '{print $5}'";
		$regExShell		= "(".$username."@)";
		$shellName		= strtolower(trim($shellObj->exeCmd($strCmd, $regExShell)));
		
		if (preg_match("/-bash/", $shellName)) {
			$childShell			= new \MTS\Common\Devices\Shells\Bash();
			$shellObj->setChildShell($childShell);
			return $childShell;
		} else {
			//unknown shell type, try to exit
			$shellObj->exeCmd("exit", preg_quote($shellObj->getShellPrompt()));
			throw new \Exception(__METHOD__ . ">> Not Handled for Shell Name: " . $shellName);
		}

		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
	private function connectByUsernameToMikrotik($shellObj, $username, $password, $ipaddress, $port)
	{
		$requestType		= $this->_classStore['requestType'];
		
		//logged in, make sure the username includes disabling colors
		$validLogin		= true;
		$cleanUsername	= $username;
		preg_match("/(.*?)\+(.*)/", $username, $addName);
			
		if (isset($addName[2]) === false) {
			//username does not include any options
			$username	= $username . "+" . $this->getMtTermOptions();
			$validLogin	= false;
		} else if ($addName[2] != $this->getMtTermOptions()) {
			//username has the wrong options
			$cleanUsername	= $addName[1];
			$username		= $addName[1] . "+" . $this->getMtTermOptions();
			$validLogin		= false;
		}
			
		if ($validLogin === false) {
				
			if ($shellObj->getDebug() === true) {
				$shellObj->addDebugData(__METHOD__ . ">> Redoing Connection, terminal options are incorrect, avoid by setting username to: " . $cleanUsername . "+" . $this->getMtTermOptions());
			}
			//since we cutoff the return, we have to make sure the welcome text is done
			//we cannot assume the class ends in any kind of prompt since terminal colors may be enabled
			$shellObj->exeCmd("", "]\s+\>");
			//then we can quit
			$shellObj->exeCmd("/quit", preg_quote($shellObj->getShellPrompt()));
				
			//then back in with a properly formatted username
			$newShell	= $this->connectByUsername($shellObj, $username, $password, $ipaddress, $port);
			return $newShell;
				
		} else {
			$childShell			= new \MTS\Common\Devices\Shells\RouterOS();
			//terminal options dictate the terminal width for ROS
			preg_match("/([0-9]+)w/", $this->getMtTermOptions(), $colCount);
			$childShell->columnCount	= $colCount[1];
			$shellObj->setChildShell($childShell);
			return $childShell;
		}
		//NFSMKO UIF NBHKD
		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}