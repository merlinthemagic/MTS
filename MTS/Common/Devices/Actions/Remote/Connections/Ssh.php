<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Remote\Connections;
use MTS\Common\Devices\Actions\Remote\Base;

class Ssh extends Base
{
	public function connectByUsername($shellObj, $username, $password, $ipaddress, $port=22)
	{
		//return the name of the user php is executed as
		$this->_classStore['requestType']	= __FUNCTION__;
		$this->_classStore['shellObj']		= $shellObj;
		$this->_classStore['username']		= $username;
		$this->_classStore['password']		= $password;
		$this->_classStore['ipaddress']		= $ipaddress;
		$this->_classStore['port']			= $port;
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
			
			//remove the username and password, they are too sensetive to keep around in case the object is dumped
			unset($this->_classStore['username']);
			unset($this->_classStore['password']);

			if ($osObj->getType() == "Linux") {
				
				$returnConn	= null;
				try {
					$connCmd		= "ssh -p ".$port." -o \"StrictHostKeyChecking no\" -o \"GSSAPIAuthentication=no\" ".$username."@".$ipaddress."";
					$regExConn		= "(".$ipaddress."'s password:|No route to host|Could not resolve hostname)";
					$connReturn		= $shellObj->exeCmd($connCmd, $regExConn);
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
							
						$regExPass	= "(Permission denied|".$username."@)";
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
						
					if ($returnPass == $username."@") {
							
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
					}
						
				} elseif ($returnConn == "No route to host") {
					throw new \Exception(__METHOD__ . ">> SSH: No route to host");
				} elseif ($returnConn == "Could not resolve hostname") {
					throw new \Exception(__METHOD__ . ">> SSH: Could not resolve hostname: " . $ipaddress);
				}
			}
		}

		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}