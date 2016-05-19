<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Local\Host;
use MTS\Common\Devices\Actions\Local\Base;

class ApplicationPaths extends Base
{
	public function getExecutionFile($appName)
	{
		$this->_classStore['requestType']	= __FUNCTION__;
		$this->_classStore['appName']		= $appName;
		return $this->execute();
	}
	public function getSudoEnabled($appName)
	{
		//tests if the user can execute a particular app with sudo
		$this->_classStore['requestType']	= __FUNCTION__;
		$this->_classStore['appName']		= $appName;
		return $this->execute();
	}
	private function execute()
	{
		$requestType		= $this->_classStore['requestType'];
		
		if ($requestType == 'getExecutionFile') {
			
			$localOsObj		= $this->getLocalOsObj();
			$osType			= $localOsObj->getType();
			
			if ($osType == 'Linux') {
				$cmdString		= "which ".$this->_classStore['appName']."";
				$cReturn		= $this->shellExec($cmdString);
				$path			= trim($cReturn);

				if (strlen($path) > 0) {
					
					$dirs		= explode(DIRECTORY_SEPARATOR, $path);
					$fileName	= array_pop($dirs);
					$exePath	= implode(DIRECTORY_SEPARATOR, $dirs);

					$fileObj	= \MTS\Factories::getFiles()->getFile($fileName, $exePath);
					return $fileObj;
					
				} else {
					//no path exists
					return false;
				}
			}
		} elseif ($requestType == 'getSudoEnabled') {
			
			$osType		= $this->getLocalOsObj()->getType();

			if ($osType == 'Linux') {
				$appName	= $this->_classStore['appName'];
				//first check that sudo is installed
				$sudoExist	= $this->getExecutionFile("sudo");
				
				//same variable used, revert it
				$this->_classStore['appName']	= $appName;
				
				if ($sudoExist === false) {
					return false;
				} else {
					//try the generic --help to determine if the app will respond through sudo
					$cmdString		= $sudoExist->getPathAsString() . " ".$this->_classStore['appName']." --help";
					$cReturn		= trim($this->shellExec($cmdString));
					
					if (strlen($cReturn) > 0) {
						return true;
					} else {
						return false;
					}
				}
			}
		}

		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}