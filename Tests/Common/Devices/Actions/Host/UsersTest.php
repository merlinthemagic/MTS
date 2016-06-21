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
		$switchUsername	= \MtsUnitTestDevices::$switchUsername;
		if ($switchUsername != "") {
			$switchPassword	= \MtsUnitTestDevices::$switchPassword;
			$shellObj		= \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', false);
			$result			= \MTS\Factories::getActions()->getRemoteUsers()->changeUser($shellObj, $switchUsername, $switchPassword);
			$this->assertInternalType("object", $result);
			$shellObj->terminate();
		}
	}
	
	//Real Device Testing
	public function test_getUsernameRealDevice()
	{
		$deviceObj	= \MtsUnitTestDevices::getDevice();
		if ($deviceObj !== null) {
			$result		= \MTS\Factories::getActions()->getRemoteUsers()->getUsername($deviceObj->getShell());
			$this->assertInternalType("string", $result);
		}
	}
}