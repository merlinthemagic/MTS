<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Local\Host;
use MTS\Common\Devices\Actions\Local\Base;

class PhpEnvironment extends Base
{
	public function getIniTimezone()
	{
		$this->_classStore['requestType']	= __FUNCTION__;
		return $this->execute();
	}
	public function getIniFile()
	{
		$this->_classStore['requestType']	= __FUNCTION__;
		return $this->execute();
	}
	public function getFunctionEnabled($functionName)
	{
		$this->_classStore['requestType']	= __FUNCTION__;
		$this->_classStore['functionName']	= $functionName;
		return $this->execute();
	}
	public function isConnectedToInternet()
	{
		$this->_classStore['requestType']	= __FUNCTION__;
		return $this->execute();
	}
	private function execute()
	{
		$requestType		= $this->_classStore['requestType'];
		
		if ($requestType == 'getIniTimezone') {
			$timezone	= trim(ini_get('date.timezone'));
			if ($timezone == "") {
				return false;
			} else {
				return new \DateTimeZone($timezone);
			}
		} elseif ($requestType == 'getIniFile') {
			$iniLocation	= php_ini_loaded_file();
			$dirs			= explode(DIRECTORY_SEPARATOR, $iniLocation);
			$fileName		= array_pop($dirs);
			$exePath		= implode(DIRECTORY_SEPARATOR, $dirs);
			
			return \MTS\Factories::getFiles()->getFile($fileName, $exePath);
		} elseif ($requestType == 'isConnectedToInternet') {
			
			//expand so we first check if we can resolve domains, or we get a false positive
			
			$fp1 = @fsockopen("www.google.com", 80, $errno, $errstr, 4);
			if ($fp1 === false){
				//maybe we are in China or Cuba
				$fp2 = @fsockopen("www.wikipedia.org", 80, $errno, $errstr, 4);
				if ($fp2 === false) {
					return false;
				} else {
					fclose($fp2);
					return true;
				}
			} else {
				fclose($fp1);
				return true;
			}
		} elseif ($requestType == 'getFunctionEnabled') {
			
			$functionName	= $this->_classStore['functionName'];
			if (function_exists($functionName) === false) {
				return false;
			} else {
				return true;
			}
		}

		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}