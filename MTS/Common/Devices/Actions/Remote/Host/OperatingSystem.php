<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Remote\Host;
use MTS\Common\Devices\Actions\Remote\Base;

class OperatingSystem extends Base
{
	public function getOsObj($shellObj)
	{
		$this->_classStore['requestType']	= __FUNCTION__;
		$this->_classStore['shellObj']		= $shellObj;
		return $this->execute();
	}
	private function execute()
	{
		$requestType		= $this->_classStore['requestType'];
		$shellObj			= $this->_classStore['shellObj']->getActiveShell();
		$cacheId			= $requestType . "_" .$shellObj->getShellUUID();
	
		if ($requestType == 'getOsObj') {
	
			//the OS is constantly needed when other Actions want to determine what command syntax to use
			//it also does not change without a reboot, so it is safe to cache.
			if (isset($this->_classStore[$cacheId]) === false) {
	
				//we need 4 things to determine the correct class of OS
				$osArch				= null;
				$osType				= null;
				$osName				= null;
				$osVersion			= null;
				
				//try to infer the OS type from the shell type.
				if ($shellObj instanceof \MTS\Common\Devices\Shells\Bash) {
					//guessing Linux
					
					//systemd standardized the release file back in 2012
					//lets always try that file first
					$reFiles	= array();
					$reFiles[]	= "/etc/os-release";
					$reFiles[]	= "/etc/centos-release";
					$reFiles[]	= "/etc/redhat-release";
					$reFiles[]	= "/etc/system-release";
					$reFiles[]	= "/etc/lsb-release";
					
					foreach ($reFiles as $reFile) {
						
						$strCmd		= "cat " . $reFile;
						$cReturn	= $shellObj->exeCmd($strCmd);
						
						if (preg_match("/No such file/i", $cReturn) == 0) {
						
							if ($osName === null) {
								if (preg_match("/(centos|debian|ubuntu|arch|red hat)/i", $cReturn, $rawName) == 1) {
									$osName		= strtolower($rawName[1]);
									$osType		= 'linux';
								}
							}
							if ($osVersion === null) {
								if (preg_match("/VERSION_ID=\"([0-9]+)/", $cReturn, $rawVer) == 1) {
									$osVersion		= $rawVer[1];
								} elseif (preg_match("/release\s([0-9]+)/i", $cReturn, $rawVer) == 1) {
									$osVersion		= $rawVer[1];
								} elseif ($osName == 'arch') {
									//Arch dists have their version in a different location
									$archCmd	= "cat /proc/version";
									$archReturn	= $shellObj->exeCmd($archCmd);
									if (preg_match("/([0-9]{8})/", $archReturn, $rawVer) == 1) {
										$osVersion		= $rawVer[1];
									}
								}
							}
					
							if ($osName !== null && $osVersion !== null) {
								break;
							}
						}
					}
					
					//architecture
					$strCmd		= "uname -a";
					$cReturn	= $shellObj->exeCmd($strCmd);
					if (preg_match("/(x86_64|i386|i686)/i", $cReturn, $rawArch) == 1) {
						$rawArch	= strtolower($rawArch[1]);
						if ($rawArch == "x86_64") {
							$osArch	= 64;
						} elseif ($rawArch == "i386" || $rawArch == "i686") {
							$osArch	= 32;
						}
					}
						
				} elseif ($shellObj instanceof \MTS\Common\Devices\Shells\RouterOS) {
						
					$osType			= 'mikrotik';
					$osName			= 'routeros';
						
					$cmdString		= '/system resource print';
					$reData			= $shellObj->exeCmd($cmdString);
					if (preg_match("/architecture-name:(.*)/", $reData, $rawAttr) == 1) {
						$osArch		= trim($rawAttr[1]);
					}
					if (preg_match("/version:\s([0-9]+)(\.[0-9]+)?/", $reData, $rawAttr) == 1) {
						$osVersion	= trim($rawAttr[1]);
					}
						
				} elseif ($shellObj instanceof \MTS\Common\Devices\Shells\PowerShell) {
						
					$osType			= 'windows';
					$osName			= 'windows';
						
					$strCmd			= '(Get-WmiObject -class Win32_OperatingSystem).Name';
					$reData			= $shellObj->exeCmd($strCmd);
					if (preg_match("/Microsoft\s+Windows\s+(.+?)\|/i", $reData, $rawVersion) == 1) {
						$osVersion	= strtolower($rawVersion[1]);
					}
						
					$strCmd			= '(Get-WmiObject -class Win32_OperatingSystem).OSArchitecture';
					$reData			= $shellObj->exeCmd($strCmd);
					if (preg_match("/(64-bit|32-bit)/i", $reData, $rawArch) == 1) {
						$osArch		= strtolower($rawArch[1]);
					}
				}
	
				if ($osType === null) {
					throw new \Exception(__METHOD__ . ">> Could not determine OS Type");
				} elseif ($osName === null) {
					throw new \Exception(__METHOD__ . ">> Could not determine OS distribution");
				} elseif ($osVersion === null) {
					throw new \Exception(__METHOD__ . ">> Could not determine OS version");
				} elseif ($osArch === null) {
					throw new \Exception(__METHOD__ . ">> Could not determine OS Architecture");
				}
	
				$osObj	= null;
				if ($osType == 'linux') {
					if ($osName == 'centos') {
						$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\CentOSBase();
					} elseif ($osName == 'red hat') {
						$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\RHELBase();
					} elseif ($osName == 'debian') {
						$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\DebianBase();
					} elseif ($osName == 'ubuntu') {
						$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\UbuntuBase();
					} elseif ($osName == 'arch') {
						$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\ArchBase();
					}
						
				} elseif ($osType == 'windows') {
					if ($osName == 'windows') {
						$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Microsoft\Windows();
					}
				}
	
				if ($osObj !== null) {
						
					$osObj->setMajorVersion($osVersion);
					$osObj->setArchitecture($osArch);
					$this->_classStore[$cacheId]	= $osObj;
					return $this->_classStore[$cacheId];
						
				} else {
					throw new \Exception(__METHOD__ . ">> OS Type: " . $osType . " and name:" . $osName . ", not handled");
				}
	
			} else {
				return $this->_classStore[$cacheId];
			}
		}
	
		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}