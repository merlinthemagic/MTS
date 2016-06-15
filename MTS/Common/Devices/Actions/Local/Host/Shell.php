<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Local\Host;
use MTS\Common\Devices\Actions\Local\Base;

class Shell extends Base
{
	public function getShell($shellType, $asRoot=false, $enableDebug=false)
	{
		$this->_classStore['requestType']	= __FUNCTION__;
		$this->_classStore['shellType']		= $shellType;
		$this->_classStore['asRoot']		= $asRoot;
		$this->_classStore['enableDebug']	= $enableDebug;
		return $this->execute();
	}
	private function execute()
	{
		$requestType		= $this->_classStore['requestType'];
		
		if ($requestType == 'getShell') {
			$shellType		= strtolower($this->_classStore['shellType']);
			$asRoot			= $this->_classStore['asRoot'];
			$enableDebug	= $this->_classStore['enableDebug'];
			$osObj			= $this->getLocalOsObj();
			
			if ($osObj->getType() == 'Linux') {
				if ($shellType == 'bash') {
					
					//get bash exe path
					$bashExe	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile('bash');
						
					if ($bashExe !== false) {
					
						$fileFact		= \MTS\Factories::getFiles();
						$pipeUuid		= uniqid();
						$workPath		= $fileFact->getDirectory(MTS_WORK_PATH . DIRECTORY_SEPARATOR . "LHS_" . $pipeUuid);
					
						$stdIn			= $fileFact->getFile("stdIn", $workPath->getPathAsString());
						$stdOut			= $fileFact->getFile("stdOut", $workPath->getPathAsString());
						$stdErr			= $fileFact->getFile("stdErr", $workPath->getPathAsString());
					
						//get screen exe path
						$screenExe	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile('screen');
					
						//get python exe path
						$pythonExe	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile('python');
					
						if ($screenExe !== false || $pythonExe !== false) {
							
							if ($asRoot === true) {
								$sudoEnabled	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getSudoEnabled('python');
								if ($sudoEnabled === true) {
									$exeCmd		= "sudo ".$pythonExe->getPathAsString()." -c \"import pty,os; pty.spawn(['".$screenExe->getPathAsString()."', '-s', '".$bashExe->getPathAsString()."', '-h', '5000', '-S', '" . $pipeUuid . "_screen']);\"";
								} else {
									$username	= \MTS\Factories::getActions()->getLocalOperatingSystem()->getUsername();
									throw new \Exception(__METHOD__ . ">> Cannot obtain root shell access. ".$username." does not have rights to sudo python");
								}
									
							} else {
								$exeCmd		= "".$pythonExe->getPathAsString()." -c \"import pty,os; pty.spawn(['".$screenExe->getPathAsString()."', '-s', '".$bashExe->getPathAsString()."', '-h', '5000', '-S', '" . $pipeUuid . "_screen']);\"";
							}
						
							//on RHEL 7 the xterm TERm will show a duplicate PS1 command that cannot be removed, also added a sleep 2s before deleting the std files, that way the files exist on the termination read / write
							$term		= 'vt100';
							$strCmd		= "mkfifo ".$stdIn->getPathAsString()."; ( sleep 1000d > ".$stdIn->getPathAsString()." & ( export TERM=".$term."; SLEEP_PID=$! ; " . $exeCmd." < ".$stdIn->getPathAsString()." > ".$stdOut->getPathAsString()." 2> ".$stdErr->getPathAsString()."; sleep 2s; rm -rf ".$stdIn->getPathAsString()."; rm -rf ".$stdOut->getPathAsString()."; rm -rf ".$stdErr->getPathAsString()."; rm -rf ".$workPath->getPathAsString()."; kill -s TERM \$SLEEP_PID & ) & ) > /dev/null 2>&1";
						
							//make the directory and out + err files
							$fileFact->getFilesTool()->create($stdOut);
							$fileFact->getFilesTool()->create($stdErr);
						
							//execute the command
							exec($strCmd);
	
							$errObj	= null;
							try {
									
								//sleep here so any error has time to be written to the stdErr file, the auto delete of error
								//will not happen until a few sec after the process is terminated
								usleep(10000);
								clearstatcache(true, $stdErr->getPathAsString());
								$fileFact->getFilesTool()->getContent($stdErr);
								if ($stdErr->getContent() != "") {
									throw new \Exception(__METHOD__ . ">> Failed to setup shell on localHost Error: " . trim($stdErr->getContent()));
								}
									
								//if the server is busy it could take a bit to setup the shell
								$maxWait	= 30;
								$eTime		= time() + $maxWait;
								$stdInOk	= false;
								while ($eTime > time()) {
									$stdInOk	= $fileFact->getFilesTool()->isFile($stdIn);
									if ($stdInOk === true) {
										break;
									} else {
										usleep(50);
									}
								}
									
								if ($stdInOk !== true) {
									throw new \Exception(__METHOD__ . ">> Failed to setup shell on localHost stdIn was never created");
								}
									
							} catch (\Exception $e) {
								switch($e->getCode()){
									default;
									$errObj = $e;
								}
							}
								
							if ($errObj === null) {
									
								//all good shell was created
								$stdPipe	= $fileFact->getProcessPipe($stdIn, $stdOut, $stdErr);
								
								$bashShell	= new \MTS\Common\Devices\Shells\Bash();
								$bashShell->setPipes($stdPipe);
								$bashShell->setDebug($enableDebug);
								
								return $bashShell;
								
							} else {
									
								//clean up
								$fileFact->getFilesTool()->delete($stdIn);
								$fileFact->getFilesTool()->delete($stdOut);
								$fileFact->getFilesTool()->delete($stdErr);
								$fileFact->getDirectoriesTool()->delete($workPath);
									
								throw $errObj;
							}
							
						} else {
							throw new \Exception(__METHOD__ . ">> Python or Screen not available on localHost");
						}

					} else {
						throw new \Exception(__METHOD__ . ">> Bash not available on localHost");
					}
						
				} else {
					throw new \Exception(__METHOD__ . ">> Not able to setup shell of type: " . $shellName);
				}
			}
		}
		
		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}