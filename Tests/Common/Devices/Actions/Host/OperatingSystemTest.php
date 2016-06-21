<?php
//© 2016 Martin Madsen
class OperatingSystemTest extends PHPUnit_Framework_TestCase
{
	//Local
	public function test_getOsObjLocal()
	{
		$result	= \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
		$this->assertInternalType("object", $result);
	}
	
	//Remote
	public function test_getOsObjRemote()
	{
		//Using Local shellObj
		$shellObj	= \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', false);
		$result		= \MTS\Factories::getActions()->getRemoteOperatingSystem()->getOsObj($shellObj);
		$this->assertInternalType("object", $result);
		$shellObj->terminate();
	}
	
	//Real Device Testing
	public function test_getOsObjRealDevice()
	{
		$deviceObj	= \MtsUnitTestDevices::getDevice();
		if ($deviceObj !== null) {
			$result		= \MTS\Factories::getActions()->getRemoteOperatingSystem()->getOsObj($deviceObj->getShell());
			$this->assertInternalType("object", $result);
		}
	}
}