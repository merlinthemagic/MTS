<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Local\Host;
use MTS\Common\Devices\Actions\Local\Base;

class Users extends Base
{
	public function getUsername()
	{
		//return the name of the user php is executed as
		$this->_classStore['requestType']	= __FUNCTION__;
		return $this->execute();
	}
	private function execute()
	{
		$requestType	= $this->_classStore['requestType'];
		$osObj			= \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
		
		 if ($requestType == 'getUsername') {
		 	if ($osObj->getType() == "Linux") {
				$cmdString		= "whoami";
				$cReturn		= $this->shellExec($cmdString);
				return trim($cReturn);
			}
		}

		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}