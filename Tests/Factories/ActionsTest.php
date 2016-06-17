<?php
//© 2016 Martin Madsen
class ActionsTest extends PHPUnit_Framework_TestCase
{
	public function test_getLocalOperatingSystem()
	{
		$result	= \MTS\Factories::getActions()->getLocalOperatingSystem();
		$this->assertInstanceOf("MTS\Common\Devices\Actions\Local\Host\OperatingSystem", $result);
	}
	public function test_getLocalApplicationPaths()
	{
		$result	= \MTS\Factories::getActions()->getLocalApplicationPaths();
		$this->assertInstanceOf("MTS\Common\Devices\Actions\Local\Host\ApplicationPaths", $result);
	}
	public function test_getLocalProcesses()
	{
		$result	= \MTS\Factories::getActions()->getLocalProcesses();
		$this->assertInstanceOf("MTS\Common\Devices\Actions\Local\Host\Processes", $result);
	}
	public function test_getLocalShell()
	{
		$result	= \MTS\Factories::getActions()->getLocalShell();
		$this->assertInstanceOf("MTS\Common\Devices\Actions\Local\Host\Shell", $result);
	}
	public function test_getLocalBrowser()
	{
		$result	= \MTS\Factories::getActions()->getLocalBrowser();
		$this->assertInstanceOf("MTS\Common\Devices\Actions\Local\Host\Browser", $result);
	}
	public function test_getLocalPhpEnvironment()
	{
		$result	= \MTS\Factories::getActions()->getLocalPhpEnvironment();
		$this->assertInstanceOf("MTS\Common\Devices\Actions\Local\Host\PhpEnvironment", $result);
	}
	
	
	//remote actions
	public function test_getRemoteUsers()
	{
		$result	= \MTS\Factories::getActions()->getRemoteUsers();
		$this->assertInstanceOf("MTS\Common\Devices\Actions\Remote\Host\Users", $result);
	}
	public function test_getRemoteOperatingSystem()
	{
		$result	= \MTS\Factories::getActions()->getRemoteOperatingSystem();
		$this->assertInstanceOf("MTS\Common\Devices\Actions\Remote\Host\OperatingSystem", $result);
	}
	public function test_getRemoteConnectionsSsh()
	{
		$result	= \MTS\Factories::getActions()->getRemoteConnectionsSsh();
		$this->assertInstanceOf("MTS\Common\Devices\Actions\Remote\Connections\Ssh", $result);
	}
}