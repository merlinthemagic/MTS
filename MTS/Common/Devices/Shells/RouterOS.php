<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Shells;

class RouterOS extends Base
{
	private $_procPipe=null;
	private $_shellPrompt=null;
	private $_strCmdCommit=null;
	private $_cmdSigInt=null;
	private $_cmdMaxTimeout=null;
	private $_termBreakDetail=array();
	private $_baseShellPPID=null;
	
	public function setPipes($procPipeObj)
	{
		$this->_procPipe		= $procPipeObj;
	}
	public function getPipes()
	{
		$parentShell	= $this->getParentShell();
		if ($parentShell === null) {
			return $this->_procPipe;
		} else {
			return $parentShell->getPipes();
		}
	}
	public function getMaxExecutionTime()
	{
		//returns time until max_execution_time is exceeded
		$curRunTime				= (\MTS\Factories::getTime()->getEpochTool()->getCurrentMiliTime() - MTS_EXECUTION_START);
		$this->_cmdMaxTimeout	= floor((ini_get('max_execution_time') - $curRunTime) * 1000);
		
		if ($this->_cmdMaxTimeout < 0) {
			$this->_cmdMaxTimeout = 0;
		}
		
		return $this->_cmdMaxTimeout;
	}
	protected function shellStrExecute($strCmd, $delimitor, $maxTimeout)
	{
		//this method should only be called from base::exeCmd()
		//if this method is called directly it breaks the child shell logic
		//all commands must be executed on the furthest child
		if ($this->getInitialized() !== true) {
			
			if ($this->getInitialized() === false) {
				throw new \Exception(__METHOD__ . ">> Error. Shell has been terminated, cannot execute anymore commands.");
			} else {
				$this->shellInitialize();
			}
		}
		if ($delimitor === null) {
			$delimitorProvided	= false;
			$delimitor			= preg_quote($this->_shellPrompt);
		} else {
			$delimitorProvided	= true;
		}

		$rTimeout	= $this->getMaxExecutionTime();
		if ($maxTimeout === null) {
			$maxTimeout		= $rTimeout;
		} elseif ($maxTimeout > $rTimeout) {
			throw new \Exception(__METHOD__ . ">> You must set a lower timeout value, the current max allowed is: " . $rTimeout . ", that is what remains of PHP max_execution_time");
		}

		$this->getPipes()->resetReadPosition();
		$rawCmdStr		= $strCmd . $this->_strCmdCommit;
		$wData			= $this->shellWrite($rawCmdStr);
		if (strlen($wData['error']) > 0) {
			throw new \Exception(__METHOD__ . ">> Failed to write command submit. Error: " . $wData['error']);
		} else {
			
			if ($maxTimeout > 0) {
				$rData	= $this->shellRead($delimitor, $maxTimeout);
				if (strlen($rData['error']) > 0 && $delimitor !== false) {
					if ($rData['error'] == "timeout") {
						throw new \Exception(__METHOD__ . ">> Read data timeout", 2500);
					} else {
						throw new \Exception(__METHOD__ . ">> Failed to read data. Error: " . $rData['error']);
					}
				} else {
					
					$rawData			= $rData['data'];
					$lines				= explode("\n", $rawData);
					if (count($lines) > 0) {
						//strip command if on line 1
						$expectCmd			= str_replace($this->_termBreakDetail, "", $lines[0]);
						if ($expectCmd == $rawCmdStr) {
							//command as expected
							unset($lines[0]);
						} elseif ($expectCmd == $strCmd . chr(8)) {
							//command ends in backspace. i think this happens when the command is one char too long to fit on a single line.
							unset($lines[0]);
						} else {
							//there is still a problem stripping the command line if the string command contains
							//escaped chars that should not be escaped for the command to work i.e.
							//cmd string stripped correctly: ldd "/usr/bin/ssh" | grep "=> \/" | awk '{print $3}'
							//cmd string not stripped correctly: ldd "/usr/bin/ssh" | grep "=> /" | awk '{print $3}'
							//bash does not care if the / is escaped, and both commands work, but this function will
							//not strip the last one
						}
					}
					
					if (count($lines) > 0) {
						if ($delimitor !== false) {
							$lines		= array_reverse($lines);
							foreach ($lines as $linNbr => $line) {
								if (preg_match("/(.*?)?(".$delimitor.")/", $line, $lineParts)) {
									
									//found the delimitor
									if ($delimitorProvided === true) {
										//user gave the delimitor, include the data from it
										$lines[$linNbr]	= $lineParts[1] . $lineParts[2];
									} else {
										//delimitor is the shell prompt, dont include it
										
										if (strlen(trim($lineParts[1])) > 0) {
											$lines[$linNbr]	= $lineParts[1];
										} else {
											//the last line only has the prompt
											unset($lines[$linNbr]);
										}
									}
									break;
								} else {
									//this line is before the delimitor has been reached
									//remove it
									unset($lines[$linNbr]);
								}
							}
							$rawData		= implode("\n", array_reverse($lines));
							
						} else {
							//user did not want the result delimited
							$rawData		= implode("\n", $lines);
						}
					} else {
						//no lines left
						$rawData		= "";
					}
					
					unset($lines);
					
					return $rawData;
				}
				
			} else {
				// no return requested
			}
		}
	}
	protected function shellInitialize()
	{
		if ($this->getInitialized() === null) {
			$this->_initialized		= 'setup';
			
			try {
				
				//set the variables
				
				$this->_strCmdCommit			= chr(13);
				$this->_cmdSigInt				= chr(3) . $this->_strCmdCommit;
				$this->_termBreakDetail[]		= " \r";
				$promptReturn					= $this->exeCmd("", "\[(.*?)\>");

				//prompt may carry some junk back, not sure why
				$singlePrompts			= array_filter(explode("\n", $promptReturn));
				foreach ($singlePrompts as $singlePrompt) {
					$singlePrompt	= trim($singlePrompt);
					if (preg_match("/^(\[(.*?)\>)$/", $singlePrompt) == 1) {
						$this->_shellPrompt	= $singlePrompt;
						break;
					}
				}
				
				if ($this->_shellPrompt === null) {
					throw new \Exception(__METHOD__ . ">> Failed to get shell prompt");
				}
				
				//for unknown reasons the prompt is sometimes written more than once initially
				//that means the first real command is offset and receives no return
				$testEnd		= (time() + 10);
				$wDone			= false;
				while ($wDone === false) {
					//since there may be many extra prompts, the delimitor must be unique
					$rosUUID		= uniqid("rosTest.", true);
					$reData			= $this->exeCmd(":put \"" . $rosUUID . "\"");
					$testReturns	= array_filter(explode("\n", $reData));
					foreach ($testReturns as $testReturn) {
						$testReturn	= trim($testReturn);
						if (preg_match("/^".preg_quote($rosUUID)."$/", $testReturn) == 1) {
							//we have a clean prompt
							$wDone		= true;
							break;
						}
					}
					
					if ($wDone === false) {
						if ($testEnd < time()) {
							throw new \Exception(__METHOD__ . ">> Failed to get clean shell");
						} else {
							//wait for output to clear
							usleep(250000);
						}
					}
				}

				//reset the output so we have a clean beginning (the test above will still leave the prompt)
				$this->getPipes()->resetReadPosition();
				
				//shell is now initialized
				$this->_initialized 			= true;
			
			} catch (\Exception $e) {
				switch($e->getCode()){
					default;
					//cleanup then throw
					$this->shellTerminate();
					throw $e;
				}
			}
		}
	}
	protected function shellTerminate()
	{
		
		if ($this->_terminating === false) {
			$this->_terminating		= true;
				
			try {

				if ($this->getInitialized() !== true) {
					//in case the shell was setup, but no commands were
					//issued, we will need to initiate before terminating
					$this->shellInitialize();
				}
		
				//make sure the last command is dead
				$this->killLastProcess();
				
				//issue the exit
				$strCmd		= "/quit";
				$delimitor	= "(closed|Welcome back\!)";
				$this->exeCmd($strCmd, $delimitor);
				
				$this->_initialized	= false;
		
			} catch (\Exception $e) {
					
				switch($e->getCode()){
					default;
					throw $e;
				}
			}
		}
	}
	protected function shellKillLastProcess()
	{
		if ($this->getInitialized() !== null && $this->getInitialized() !== false) {
	
			//SIGINT current process and get prompt
			$strCmd		= $this->_cmdSigInt;
			$this->exeCmd($strCmd);
		}
	}
	private function shellWrite($strCmd)
	{
		$return['error']	= null;
		$return['stime']	= \MTS\Factories::getTime()->getEpochTool()->getCurrentMiliTime();
		try {
			$this->getPipes()->strWrite($strCmd);
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				$return['errorMsg']	= $e->getMessage();
			}
		}
		$return['etime']	= \MTS\Factories::getTime()->getEpochTool()->getCurrentMiliTime();
		
		if ($this->debug === true) {
			
			$debugData			= $return;
			$debugData['cmd']	= $strCmd;
			$debugData['type']	= __FUNCTION__;
			$this->addDebugData($debugData);
		}
		return $return;
	}
	private function shellRead($regex=false, $maxWaitMs=0)
	{
		//getCurrentMiliTime returns a decimal
		$maxWaitMs			= $maxWaitMs / 1000;
		$return['error']	= null;
		$return['data']		= null;
		$lDataTime			= \MTS\Factories::getTime()->getEpochTool()->getCurrentMiliTime();
		$return['stime']	= $lDataTime;
		$done				= false;

		try {
			while ($done === false) {
				$newData	= $this->getPipes()->strRead();
				$exeTime	= \MTS\Factories::getTime()->getEpochTool()->getCurrentMiliTime();
				
				if ($newData != "") {
					$lDataTime			= $exeTime;
					$return['data']		.= $newData;
					if ($regex !== false && preg_match("/".$regex."/", $return['data'])) {
						//found pattern match
						$done	= true;
					}
				}
				if ($done === false && ($exeTime - $return['stime']) > $maxWaitMs) {
					//timed out
					$return['error']	= 'timeout';
					$done				= true;
				}
			}
		} catch (\Exception $e) {
	
			switch($e->getCode()) {
				default;
				$return['error']	= $e->getMessage();
			}
		}
		
		$return['etime']	= \MTS\Factories::getTime()->getEpochTool()->getCurrentMiliTime();
		
		if ($this->debug === true) {
			$debugData				= $return;
			$debugData['type']		= __FUNCTION__;
			$debugData['regex']		= $regex;
			$debugData['timeout']	= $maxWaitMs;
			$this->addDebugData($debugData);
		}
		return $return;
	}
}