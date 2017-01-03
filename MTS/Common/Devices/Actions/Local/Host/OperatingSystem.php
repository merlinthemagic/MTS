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

				$osObj				= null;
				
				//we need 4 things to determine the correct class of OS
				$osArch				= null;
				$osType				= null;
				$osName				= null;
				$osMajorVersion		= null;
					
				$osDetail			= php_uname();
				if (preg_match("/^Linux\s/", $osDetail)) {
					$osType			= 'linux';

					preg_match("/(x86_64|i386|i686)/i", $osDetail, $rawArch);
					if (isset($rawArch[1])) {
						$rawArch	= strtolower($rawArch[1]);
						if ($rawArch == "x86_64") {
							$osArch	= 64;
						} elseif ($rawArch == "i386" || $rawArch == "i686") {
							$osArch	= 32;
						}
					}
					
					$rFile		= "/etc/os-release";
					$rFile2		= "/etc/redhat-release";
					$rFile3		= "/etc/centos-release";
					$cmdString	= null;
					if (file_exists($rFile) === true) {
						$cmdString		= "cat " . $rFile;
					} elseif (file_exists($rFile2) === true) {
						$cmdString		= "cat " . $rFile2;
					} elseif (file_exists($rFile3) === true) {
						$cmdString		= "cat " . $rFile3;
					}
					
					$cReturn	= null;
					if ($cmdString !== null) {
						$cReturn		= $this->shellExec($cmdString);
					}

					if ($cReturn !== null) {

						if (preg_match("/NAME=\"(CentOS Linux|Debian GNU\/Linux|Ubuntu|Arch Linux)\"/i", $cReturn, $rawName) == 1) {
							$osName				= strtolower($rawName[1]);
						} elseif (preg_match("/CentOS/i", $cReturn, $rawName) == 1) {
							$osName				= "centos linux";
						}
						
						if ($osName == 'arch linux') {
							$cmdString		= 'cat /proc/version';
							$cReturn		= $this->shellExec($cmdString);
							
							preg_match("/([0-9]{8})/", $cReturn, $rawMajorVersion);
							
							if (isset($rawMajorVersion[1]) === true) {
								$osMajorVersion		= $rawMajorVersion[1];
							}
							
						} else {
							
							if (preg_match("/VERSION_ID=\"([0-9]+)/", $cReturn, $rawMajorVersion) == 1) {
								$osMajorVersion		= $rawMajorVersion[1];
							} elseif (preg_match("/([0-9]+)/", $cReturn, $rawMajorVersion) == 1) {
								//solve permanentely for centos minor 7,8 "CentOS release 6.8 (Final)"
								$osMajorVersion		= $rawMajorVersion[1];
							}
						}
					}
					
				} elseif (preg_match("/^Windows\s/", $osDetail)) {
					
					$osType			= 'windows';
					$osName			= 'windows';
					
					$cmdString		= "wmic OS get Name";
					$cReturn		= $this->shellExec($cmdString);
					preg_match("/Microsoft\s+Windows\s+(.+?)\|/i", $cReturn, $rawVersion);
					if (isset($rawVersion[1])) {
						$rawVer			= strtolower($rawVersion[1]);
						$osMajorVersion	= $rawVer;
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

				if (
					$osType !== null
					&& $osName !== null
					&& $osMajorVersion !== null
					&& $osArch !== null
				) {
					$osObj	= null;
					if ($osType == 'linux') {
						if ($osName == 'centos linux') {
							$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\CentOSBase();
						} elseif ($osName == 'red hat enterprise linux') {
							$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\RHELBase();
						} elseif ($osName == 'debian gnu/linux') {
							$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\DebianBase();
						} elseif ($osName == 'ubuntu') {
							$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\UbuntuBase();
						} elseif ($osName == 'arch linux') {
							$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\ArchBase();
						}
					} elseif ($osType == 'windows') {
						if ($osName == 'windows') {
							$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Microsoft\Windows();
						}
					}
					
					if ($osObj !== null) {
						$osObj->setMajorVersion($osMajorVersion);
						$osObj->setArchitecture($osArch);
						$this->_classStore[$cacheId]	= $osObj;
						return $this->_classStore[$cacheId];
					}
				}
				
			} else {
				return $this->_classStore[$cacheId];
			}
		}

		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}