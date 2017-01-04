<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Local\Host;
use MTS\Common\Devices\Actions\Local\Base;

class OperatingSystem extends Base
{
	public function getOsObj()
	{
		$this->_classStore['requestType']	= __FUNCTION__;
		return $this->execute();
	}
	private function execute()
	{
		$requestType		= $this->_classStore['requestType'];
		$cacheId			= $requestType . "_";
		
		if ($requestType == 'getOsObj') {

			//the OS is constantly needed when other Actions want to determine what command syntax to use
			//it also does not change without a reboot, so it is safe to cache.
			if (isset($this->_classStore[$cacheId]) === false) {

				//we need 4 things to determine the correct class of OS
				$osArch				= null;
				$osType				= null;
				$osName				= null;
				$osVersion			= null;
					
				$osDetail			= php_uname();
				if (preg_match("/^Linux\s/i", $osDetail)) {
					$osType			= 'linux';
					if (preg_match("/(x86_64|i386|i686)/i", $osDetail, $rawArch) == 1) {
						$rawArch	= strtolower($rawArch[1]);
						if ($rawArch == "x86_64") {
							$osArch	= 64;
						} elseif ($rawArch == "i386" || $rawArch == "i686") {
							$osArch	= 32;
						}
					}
					
					//systemd standardized the release file back in 2012
					//lets always try that file first
					$reFiles	= array();
					$reFiles[]	= "/etc/os-release";
					$reFiles	= array_unique(array_merge($reFiles, glob('/etc/*-release')));

					foreach ($reFiles as $reFile) {
						if (file_exists($reFile) === true) {
							$strCmd		= "cat " . $reFile;
							$cReturn	= $this->shellExec($strCmd);
							
							if ($osName === null) {
								if (preg_match("/(centos|debian|ubuntu|arch|red hat)/i", $cReturn, $rawName) == 1) {
									$osName		= strtolower($rawName[1]);
								}
							}
							if ($osVersion === null) {
								if (preg_match("/VERSION_ID=\"([0-9]+)/", $cReturn, $rawVer) == 1) {
									$osVersion		= $rawVer[1];
								} elseif (preg_match("/release\s([0-9]+)/i", $cReturn, $rawVer) == 1) {
									$osVersion		= $rawVer[1];
								} elseif (preg_match("/DISTRIB_RELEASE=([0-9]+)/i", $cReturn, $rawVer) == 1) {
									$osVersion		= $rawVer[1];
								} elseif ($osName == 'arch') {
									//Arch dists have their version in a different location
									$archCmd	= "cat /proc/version";
									$archReturn	= $this->shellExec($archCmd);
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

				} elseif (preg_match("/^Windows\s/i", $osDetail)) {
					
					$osType			= 'windows';
					$osName			= 'windows';
					
					$cmdString		= "wmic OS get Name";
					$cReturn		= $this->shellExec($cmdString);
					preg_match("/Microsoft\s+Windows\s+(.+?)\|/i", $cReturn, $rawVersion);
					if (isset($rawVersion[1])) {
						$rawVer		= strtolower($rawVersion[1]);
						$osVersion	= $rawVer;
					}
					
					$cmdString		= "wmic OS get OSArchitecture";
					$cReturn		= $this->shellExec($cmdString);
					preg_match("/(64-bit|32-bit)/i", $cReturn, $rawArch);
					if (isset($rawArch[1])) {
						$rawArch	= strtolower($rawArch[1]);
						if ($rawArch == "64-bit") {
							$osArch	= 64;
						} elseif ($rawArch == "32-bit") {
							$osArch	= 32;
						}
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