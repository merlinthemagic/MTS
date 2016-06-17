<?php
//© 2016 Martin Madsen
class UsersTest extends PHPUnit_Framework_TestCase
{
	//Local
	public function test_getUsernameLocal()
	{
		$result	= \MTS\Factories::getActions()->getLocalUsers()->getUsername();
		$this->assertInternalType("string", $result);
	}
	
	//Remote
	public function test_getUsernameRemote()
	{
		//Using Local shellObj
		$localuser	= \MTS\Factories::getActions()->getLocalUsers()->getUsername();
		$shellObj	= \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', false);
		$result		= \MTS\Factories::getActions()->getRemoteUsers()->getUsername($shellObj);
		$this->assertEquals($localuser, $result);
		$shellObj->terminate();
	}
	public function test_changeUserRemote()
	{
		//no good way to test this works without knowing another user exists
	}
	
	//Real Device Testing
	public function test_getUsernameRemoteGeneric()
	{
		$deviceObj	= \MtsUnitTestDevices::getGenericDevice();
		if ($deviceObj !== null) {
			$shellObj	= $deviceObj->getShell();
			$result		= \MTS\Factories::getActions()->getRemoteUsers()->getUsername($shellObj);
			if (\MtsUnitTestDevices::$genericCache === false) {
				$shellObj->terminate();
			}
			$this->assertInternalType("string", $result);
		}
	}
	public function test_getUsernameRemoteROS()
	{
		$deviceObj	= \MtsUnitTestDevices::getROSDevice();
		if ($deviceObj !== null) {
			$shellObj	= $deviceObj->getShell();
			$result		= \MTS\Factories::getActions()->getRemoteUsers()->getUsername($shellObj);
			if (\MtsUnitTestDevices::$rosCache === false) {
				$shellObj->terminate();
			}
			$this->assertInternalType("string", $result);
		}
	}
}