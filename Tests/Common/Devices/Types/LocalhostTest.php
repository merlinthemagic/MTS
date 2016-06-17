<?php
//© 2016 Martin Madsen
class LocalhostTest extends PHPUnit_Framework_TestCase
{
	public function test_getBrowserLocal()
	{
		$result		= \MTS\Factories::getDevices()->getLocalHost()->getBrowser('phantomjs');
		$this->assertInstanceOf("MTS\Common\Devices\Browsers\PhantomJS", $result);
		$result->terminate();
	}
	public function test_getShellLocal()
	{
		//non root
		$result		= \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', false);
		$this->assertInstanceOf("MTS\Common\Devices\Shells\Bash", $result);
		$result->terminate();
	}
	public function test_getRootShellLocal()
	{
		//if root is available
		$sudoEnabled	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getSudoEnabled('python');
		if ($sudoEnabled === true) {
			$result		= \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', true);
			$this->assertInstanceOf("MTS\Common\Devices\Shells\Bash", $result);
			$result->terminate();
		}
	}
}