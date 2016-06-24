<?php
//© 2016 Martin Madsen
class BrowserTest extends PHPUnit_Framework_TestCase
{
	//Local
	public function test_dependenciesLocal()
	{
		$result		= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile("sleep");
		$this->assertInstanceOf("MTS\Common\Data\Computer\FileSystems\File", $result);
	}
}