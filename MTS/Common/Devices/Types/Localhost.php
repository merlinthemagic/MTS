<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Types;
use \MTS\Common\Devices\Device;

class Localhost extends Device
{
	private $_classStore=array();
	
	public function getShell($shellName, $priviliged=false)
	{
		//dont cache return new instance every time
		$shellName	= strtolower($shellName);
		
		$osObj			= $this->getOS();
		
		if ($osObj->getType() == 'Linux' && $shellName == 'bash') {
			
			$pipeUuid		= uniqid();
			$workPath		= MTS_WORK_PATH . DIRECTORY_SEPARATOR . "LHS_" . $pipeUuid;
			$fileFact		= \MTS\Factories::getFiles();
			
			$stdIn			= $fileFact->getFile("stdIn", $workPath);
			$stdOut			= $fileFact->getFile("stdOut", $workPath);
			$stdErr			= $fileFact->getFile("stdErr", $workPath);
			
			//need to move to action class
			$cmdString		= "which screen";
			$cReturn		= exec($cmdString);
			$sPath			= trim($cReturn);
			
			$cmdString		= "which bash";
			$cReturn		= exec($cmdString);
			$bPath			= trim($cReturn);
			
			//make sure to make test for bash not available / or screen

			if ($priviliged === true) {
				$exeCmd		= "sudo python -c \"import pty,os; pty.spawn(['".$sPath."', '-s', '".$bPath."', '-h', '5000', '-S', '" . $pipeUuid . "_screen']);\"";
			} else {
				$exeCmd		= "python -c \"import pty,os; pty.spawn(['".$sPath."', '-s', '".$bPath."', '-h', '5000', '-S', '" . $pipeUuid . "_screen']);\"";
			}
			
			//on RHEL 7 the xterm TERm will show a duplicate PS1 command that cannot be changed
			$term		= 'vt100';
			$strCmd		= "mkfifo ".$stdIn->getPathAsString()."; ( sleep 1000d > ".$stdIn->getPathAsString()." & ( export TERM=".$term."; SLEEP_PID=$! ; " . $exeCmd." < ".$stdIn->getPathAsString()." > ".$stdOut->getPathAsString()." 2> ".$stdErr->getPathAsString()."; rm -rf ".$stdIn->getPathAsString()."; rm -rf ".$stdOut->getPathAsString()."; rm -rf ".$stdErr->getPathAsString()."; rm -rf ".$workPath."; kill -s TERM \$SLEEP_PID & ) & ) > /dev/null 2>&1";

			//make the directory and out + err files
			$fileFact->getFilesTool()->create($stdOut);
			$fileFact->getFilesTool()->create($stdErr);
			
			//execute the command
			exec($strCmd);
			
			//if the server is busy it could take a bit to setup the shell
			$maxWait	= 30;
			$eTime		= time() + $maxWait;

			while ($eTime > time()) {
				$stdInOk	= $fileFact->getFilesTool()->isFile($stdIn);
				if ($stdInOk === true) {
					break;
				} else {
					usleep(50);
				}
			}

			$stdPipe	= $fileFact->getProcessPipe($stdIn, $stdOut, $stdErr);
			
			$bashShell	= new \MTS\Common\Devices\Shells\Bash();
			$bashShell->setPipes($stdPipe);
			
			return $bashShell;

		} else {
			throw new \Exception(__METHOD__ . ">> Not Handled for OS Type: " . $osObj->getType() . ", shell name: " . $shellName);
		}
	}
	public function getOS()
	{
		if (array_key_exists(__METHOD__, $this->_classStore) === false) {
			$this->_classStore[__METHOD__]	= $this->getAF()->getLocalOperatingSystem()->getOsObj();
		}
		return $this->_classStore[__METHOD__];
	}
}