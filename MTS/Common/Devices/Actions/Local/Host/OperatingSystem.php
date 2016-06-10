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
	public function getUsername()
	{
		//return the name of the user php is executed as
		$this->_classStore['requestType']	= __FUNCTION__;
		return $this->execute();
	}
	private function execute()
	{
		$requestType		= $this->_classStore['requestType'];
		
		if ($requestType == 'getOsObj') {

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
				
				$cmdString		= 'cat /etc/os-release';
				$cReturn		= $this->shellExec($cmdString);
				
				if (strlen($cReturn) == 0) {
					$cmdString		= 'cat /etc/redhat-release';
					$cReturn		= $this->shellExec($cmdString);
				}
					
				if ($cReturn !== null) {
					preg_match("/NAME=\"(CentOS Linux|Debian GNU\/Linux|Ubuntu|Arch Linux)\"/", $cReturn, $rawName);
					
					if (isset($rawName[1]) === true) {
						$osName				= strtolower($rawName[1]);
					}
					
					if ($osName == 'arch linux') {
						$cmdString		= 'cat /proc/version';
						$cReturn		= $this->shellExec($cmdString);
						
						preg_match("/([0-9]{8})/", $cReturn, $rawMajorVersion);
						
						if (isset($rawMajorVersion[1]) === true) {
							$osMajorVersion		= $rawMajorVersion[1];
						}
						
					} else {
						preg_match("/VERSION_ID=\"([0-9]+)/", $cReturn, $rawMajorVersion);
						
						if (isset($rawMajorVersion[1]) === true) {
							$osMajorVersion		= $rawMajorVersion[1];
						}
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
				}
				
				if ($osObj !== null) {
					$osObj->setMajorVersion($osMajorVersion);
					$osObj->setArchitecture($osArch);
					return $osObj;
				}
			}

		} elseif ($requestType == 'getUsername') {
			
			$osType		= $this->getLocalOsObj()->getType();

			if ($osType == 'Linux') {
				$cmdString		= "whoami";
				$cReturn		= $this->shellExec($cmdString);
				return trim($cReturn);
			}
		}

		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}