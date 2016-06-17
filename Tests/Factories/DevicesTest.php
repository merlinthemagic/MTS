<?php
//© 2016 Martin Madsen
class DevicesTest extends PHPUnit_Framework_TestCase
{
	public function test_getLocalHost()
	{
		$result	= \MTS\Factories::getDevices()->getLocalHost();
		$this->assertInstanceOf("MTS\Common\Devices\Types\Localhost", $result);
	}
	public function test_getRemoteHost()
	{
		$result	= \MTS\Factories::getDevices()->getRemoteHost();
		$this->assertInstanceOf("MTS\Common\Devices\Types\Remotehost", $result);
	}
}