<?php
//© 2016 Martin Madsen
class ShellTest extends PHPUnit_Framework_TestCase
{
	//Local
	public function test_dependenciesLocal()
	{
		$result		= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile("sleep");
		$this->assertInstanceOf("MTS\Common\Data\Computer\FileSystems\File", $result);
		
		$result		= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile("python");
		$this->assertInstanceOf("MTS\Common\Data\Computer\FileSystems\File", $result);
		
		$result		= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile("screen");
		$this->assertInstanceOf("MTS\Common\Data\Computer\FileSystems\File", $result);
	}
}