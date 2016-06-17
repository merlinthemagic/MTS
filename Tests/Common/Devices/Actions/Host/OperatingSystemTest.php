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
	public function test_getOsObjRemoteGeneric()
	{
		$deviceObj	= \MtsUnitTestDevices::getGenericDevice();
		if ($deviceObj !== null) {
			$shellObj	= $deviceObj->getShell();
			$result		= \MTS\Factories::getActions()->getRemoteOperatingSystem()->getOsObj($shellObj);
			if (\MtsUnitTestDevices::$genericCache === false) {
				$shellObj->terminate();
			}
			$this->assertInternalType("object", $result);
		}
	}
	public function test_getOsObjRemoteROS()
	{
		$deviceObj	= \MtsUnitTestDevices::getROSDevice();
		if ($deviceObj !== null) {
			$shellObj	= $deviceObj->getShell();
			$result		= \MTS\Factories::getActions()->getRemoteOperatingSystem()->getOsObj($shellObj);
			if (\MtsUnitTestDevices::$rosCache === false) {
				$shellObj->terminate();
			}
			$this->assertInternalType("object", $result);
		}
	}
}