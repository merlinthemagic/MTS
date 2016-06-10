<?php
//© 2016 Martin Madsen
class ApplicationPathsTest extends PHPUnit_Framework_TestCase
{
	public function test_getExecutionFile()
	{
		$result	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile('bash');
		$this->assertInstanceOf("MTS\Common\Data\Computer\FileSystems\File", $result);
	}
	public function test_getSudoEnabled()
	{
		$result	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getSudoEnabled('python');
		$this->assertInternalType("bool", $result);
	}
}