<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Remote\Host;
use MTS\Common\Devices\Actions\Remote\Base;

class OperatingSystem extends Base
{
	public function getUsername($shellObj)
	{
		//return the name of the user php is executed as
		$this->_classStore['requestType']	= __FUNCTION__;
		$this->_classStore['shellObj']		= $shellObj;
		return $this->execute();
	}
	private function execute()
	{
		$requestType		= $this->_classStore['requestType'];
		$shellObj			= $this->_classStore['shellObj'];
		
		if ($requestType == 'getUsername') {
			if ($shellObj instanceof \MTS\Common\Devices\Shells\Bash) {
				$username			= trim($shellObj->exeCmd("whoami"));
				if (strlen($username) > 0) {
					return $username;
				}
			}
		}
		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}