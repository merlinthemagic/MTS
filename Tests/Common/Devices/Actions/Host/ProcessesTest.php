<?php
//© 2016 Martin Madsen
class ProcessesTest extends PHPUnit_Framework_TestCase
{
	//Local
	public function test_sigTermPidLocal()
	{
		$result		= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile("sleep");
		$this->assertInstanceOf("MTS\Common\Data\Computer\FileSystems\File", $result);
		
		$result		= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile("ps");
		$this->assertInstanceOf("MTS\Common\Data\Computer\FileSystems\File", $result);

		//make a sleep process and terminate it
		$pid	= \MTS\Factories::getActions()->getLocalProcesses()->createSleepProcess(17);
		$this->assertInternalType("int", $pid);
		
		$result	= \MTS\Factories::getActions()->getLocalProcesses()->sigTermPid($pid);
		$this->assertEmpty($result);
		
		//create another and terminate at a delay
		$pid	= \MTS\Factories::getActions()->getLocalProcesses()->createSleepProcess(17);
		$this->assertInternalType("int", $pid);
		
		$result	= \MTS\Factories::getActions()->getLocalProcesses()->sigTermPid($pid, 2);
		$this->assertEmpty($result);
		
		//after 1 second the process should still be alive
		sleep(1);
		$result	= \MTS\Factories::getActions()->getLocalProcesses()->isRunningPid($pid);
		$this->assertEquals(true, $result);
		
		//2 seconds later it should be dead
		sleep(2);
		$result	= \MTS\Factories::getActions()->getLocalProcesses()->isRunningPid($pid);
		$this->assertEquals(false, $result);
	}
}