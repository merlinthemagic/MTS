<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Local\Host;
use MTS\Common\Devices\Actions\Local\Base;

class OperatingSystem extends Base
{
	private $_classStore=array();
	
	public function getOsObj()
	{
		$this->_classStore['requestType']	= __FUNCTION__;
		return $this->execute();
	}
	private function execute()
	{
		$requestType		= $this->_classStore['requestType'];
		
		if ($requestType == 'getOsObj') {

			$osObj				= null;
			
			//we need 3 things to determine the correct class of OS
			$osType				= null;
			$osName				= null;
			$osMajorVersion		= null;
				
			$osDetail			= php_uname();
			if (preg_match("/^Linux\s/", $osDetail)) {
				$osType			= 'linux';
				$cmdString		= 'uname -o && uname -r && cat /etc/*release* | awk \'NR==1\'';
				$cReturn		= $this->shellExec($cmdString);
					
				if ($cReturn !== null) {
					preg_match("/GNU\/(Linux)/", $cReturn, $osRaw1);
					preg_match("/(.*)\.(el[0-9])\.(.*)/", $cReturn, $osRaw2);
					preg_match("/(CentOS)\s([A-Za-z\s]+)\s(([0-9]+)\.([0-9]+)|([0-9]+)(0))/", $cReturn, $osRaw3);
						
					if (isset($osRaw3[1]) === true) {
						$osName				= strtolower($osRaw3[1]);
					}
					if (isset($osRaw3[4]) === true) {
						$osMajorVersion		= $osRaw3[4];
					}
				}
			}
			
			if (
				$osType !== null
				&& $osName !== null
				&& $osMajorVersion !== null
			) {
				if ($osType == 'linux') {
			
					if ($osName == 'centos') {
							
						if ($osMajorVersion == 7) {
							$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\CentOS7();
						} elseif ($osMajorVersion == 6) {
							$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\CentOS6();
						} elseif ($osMajorVersion == 5) {
							$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\CentOS5();
						}
					}
				}
			}

			if ($osObj !== null) {
				$osObj->setActionClass($this);
				return $osObj;
			}
		}

		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}