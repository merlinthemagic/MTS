<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Local\Host;
use MTS\Common\Devices\Actions\Local\Base;

class Shell extends Base
{
	public function getShell($shellType, $asRoot=false, $enableDebug=false, $width=80, $height=24)
	{
		$this->_classStore['requestType']	= __FUNCTION__;
		$this->_classStore['shellType']		= $shellType;
		$this->_classStore['asRoot']		= $asRoot;
		$this->_classStore['enableDebug']	= $enableDebug;
		$this->_classStore['width']			= $width;
		$this->_classStore['height']		= $height;
		return $this->execute();
	}
	private function execute()
	{
		$requestType	= $this->_classStore['requestType'];
		$osObj			= \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
		
		if ($requestType == 'getShell') {
			
			$shellType		= strtolower($this->_classStore['shellType']);
			$asRoot			= $this->_classStore['asRoot'];
			$enableDebug	= $this->_classStore['enableDebug'];
			$width			= $this->_classStore['width'];
			$height			= $this->_classStore['height'];

			if ($osObj->getType() == "Linux") {
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
							
							$exeCmd		= "";
							
							if ($asRoot === true) {
								$sudoEnabled	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getSudoEnabled('python');
								if ($sudoEnabled === true) {
									$exeCmd		.= "sudo ";
								} else {
									$username	= \MTS\Factories::getActions()->getLocalUsers()->getUsername();
									throw new \Exception(__METHOD__ . ">> Cannot obtain root shell access. ".$username." does not have rights to sudo python");
								}
							}
							
							$exeCmd		.= "".$pythonExe->getPathAsString()." -c \"import pty,os; os.environ['LINES'] = '".$height."'; os.environ['COLUMNS'] = '".$width."'; pty.spawn(['".$screenExe->getPathAsString()."', '-s', '".$bashExe->getPathAsString()."', '-h', '5000', '-S', '" . $pipeUuid . "_screen', '-T', 'xterm']);\"";

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
				
			} elseif ($osObj->getType() == "windows") {
				
				if ($shellType == 'powershell') {

					$powerShellExe	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile('powershell');

					if ($powerShellExe !== false) {

						$fileFact		= \MTS\Factories::getFiles();
						$pipeUuid		= uniqid();
						$workPath		= $fileFact->getDirectory(MTS_WORK_PATH . DIRECTORY_SEPARATOR . "LHS_" . $pipeUuid);
					
						$stdIn			= $fileFact->getFile("stdIn", $workPath->getPathAsString());
						$stdOut			= $fileFact->getFile("stdOut", $workPath->getPathAsString());
						$stdErr			= $fileFact->getFile("stdErr", $workPath->getPathAsString());

						//maybe have more efficient versions for v3, 4?
						$psInit			= $fileFact->getVendorFile("psv1ctrl");

						$fileFact->getFilesTool()->create($stdIn);
						$fileFact->getFilesTool()->create($stdOut);
						$fileFact->getFilesTool()->create($stdErr);

						$exeCmd		= $powerShellExe->getPathAsString() . " -executionPolicy Unrestricted " . $psInit->getPathAsString();
						
						//the cmd width dictates the powershell width so we set it here
						//wait 2 sec before deleting the files
						$strCmd		= "START \"seq\" cmd /c \"mode con cols=".$width." && " . $exeCmd . " \"" .$workPath->getPathAsString()."\" & ping -n 2 127.0.0.1 && rmdir /s /q \"" .$workPath->getPathAsString(). "\"\"";
						
						//cannot get exec() to return without waiting for process to exit
						//should get fixed since we dont want to depend on another function for MTS to run 
						pclose(popen($strCmd, "r"));

						//do we need some validation the shell was created?
						$errObj	= null;
						
						if ($errObj === null) {
								
							//all good shell was created
							$stdPipe	= $fileFact->getProcessPipe($stdIn, $stdOut, $stdErr);
							
							$powerShell	= new \MTS\Common\Devices\Shells\PowerShell();
							$powerShell->setPipes($stdPipe);
							$powerShell->setDebug($enableDebug);
							
							return $powerShell;
							
						} else {
								
							//clean up
							$fileFact->getDirectoriesTool()->delete($workPath);
								
							throw $errObj;
						}

					} else {
						throw new \Exception(__METHOD__ . ">> Powershell not available on localHost");
					}
						
				} elseif ($shellType == 'cmd') {

					$cmdExe	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile('cmd');
					if ($cmdExe !== false) {

						$fileFact		= \MTS\Factories::getFiles();
						$pipeUuid		= uniqid();
						$workPath		= $fileFact->getDirectory(MTS_WORK_PATH . DIRECTORY_SEPARATOR . "LHS_" . $pipeUuid);
					
						$stdIn			= $fileFact->getFile("stdIn", $workPath->getPathAsString());
						$stdOut			= $fileFact->getFile("stdOut", $workPath->getPathAsString());
						$stdErr			= $fileFact->getFile("stdErr", $workPath->getPathAsString());

						$fileFact->getFilesTool()->create($stdOut);
						$fileFact->getFilesTool()->create($stdErr);

						$errObj	= null;
						try {
							
							$dPipes = array(
									0 => array("pipe", "r"),
									1 => array("file", $stdOut->getPathAsString(), "w"),
									2 => array("file", $stdErr->getPathAsString(), "a")
							);
								
							$parentProc = proc_open($cmdExe->getPathAsString(), $dPipes, $pipes);
								
							if (is_resource($parentProc)) {
								stream_set_blocking($pipes[0], 0);

								//set the terminal width
								$cmd 	="mode con cols=".$width."\r\n";
								fwrite($pipes[0], $cmd);

							} else {
								throw new \Exception(__METHOD__ . ">> Failed to start shell process on localHost");
							}
						
						} catch (\Exception $e) {
							switch($e->getCode()){
								default;
								$errObj = $e;
							}
						}


						if ($errObj === null) {
								
							//all good shell was created
							$stdPipe	= $fileFact->getProcessPipe(null, $stdOut, $stdErr);
							$stdPipe->setInputFile($pipes[0], $parentProc);
							
							$cmdShell	= new \MTS\Common\Devices\Shells\Cmd();
							$cmdShell->setPipes($stdPipe);
							$cmdShell->setDebug($enableDebug);
							
							return $cmdShell;
							
						} else {
							//clean up
							$fileFact->getDirectoriesTool()->delete($workPath);
							throw $errObj;
						}

					} else {
						throw new \Exception(__METHOD__ . ">> CMD not available on localHost");
					}
						
				} else {
					throw new \Exception(__METHOD__ . ">> Not able to setup shell of type: " . $shellName);
				}
			}
		}
		
		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}
