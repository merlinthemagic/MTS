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
		$shellObj	= \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', false);
		$result		= \MTS\Factories::getActions()->getRemoteOperatingSystem()->getOsObj($shellObj);
		$this->assertInternalType("object", $result);
		$shellObj->terminate();
	}
}