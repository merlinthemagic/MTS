<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Shells;

class Bash extends Base
{
	private $_procPipe=null;
	private $_shellPrompt=null;
	private $_strCmdCommit=null;
	private $_cmdMaxTimeout=null;
	private $_termBreakDetail=array();
	private $_baseShellPPID=null;
	private $_terminating=false;
	
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
	public function getTerminalWidth()
	{
		$strCmd		= "echo \$COLUMNS";
		$reData		= $this->exeCmd($strCmd, null, null);

		if (preg_match("/([0-9]+)/", $reData, $rawColCount)) {
			return $rawColCount[1];
		} else {
			throw new \Exception(__METHOD__ . ">> Failed to get terminal width");
		}
	}
	public function setTerminalWidth($count)
	{
		if (preg_match("/^[0-9]+$/", $count)) {
			$strCmd			= "stty columns " . $count;
			$this->exeCmd($strCmd, null, null);
			
			if ($this->getTerminalWidth() != $count) {
				throw new \Exception(__METHOD__ . ">> Failed to set terminal width");
			}
		} else {
			throw new \Exception(__METHOD__ . ">> Terminal Width can only be an integer");
		}
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
		
		$rawCmdStr	= $strCmd . $this->_strCmdCommit;
		$wData		= $this->shellWrite($rawCmdStr);
		if (strlen($wData['error']) > 0) {
			throw new \Exception(__METHOD__ . ">> Failed to write command submit. Error: " . $wData['error']);
		} else {
			
			if ($maxTimeout > 0) {
				$rData	= $this->shellRead($delimitor, $maxTimeout);
				if (strlen($rData['error']) > 0 && $delimitor !== false) {
					throw new \Exception(__METHOD__ . ">> Failed to read data. Error: " . $rData['error']);
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
				$this->_shellPrompt		= "[" . uniqid("bash.", true) . "]";
				$this->_strCmdCommit	= chr(13);
			
				//set the prompt to a known value
				$strCmd		= "PS1=\"".$this->_shellPrompt."\"" . $this->_strCmdCommit;
				$wData		= $this->shellWrite($strCmd);
				if (strlen($wData['error']) > 0) {
					throw new \Exception(__METHOD__ . ">> Failed to write shell promt command. Error: " . $wData['error']);
				}
				
				//see if it took hold
				$strCmd		= $this->_strCmdCommit . "echo \">>>>\"\$PS1\"<<<<\" && echo $$\"MERLIN\"$$"  . $this->_strCmdCommit;
				$wData		= $this->shellWrite($strCmd);
				if (strlen($wData['error']) > 0) {
					throw new \Exception(__METHOD__ . ">> Failed to write shell promt validation command. Error: " . $wData['error']);
				}
				
				$delimitor	= "[0-9]+MERLIN[0-9]+";
				$rData		= $this->shellRead($delimitor, $this->getMaxExecutionTime());
				if (strlen($rData['error']) > 0) {
					throw new \Exception(__METHOD__ . ">> Failed to read shell promt validation command. Error: " . $rData['error']);
				}
	
				$rLines	= array_reverse(explode("\n", $rData['data']));
				foreach ($rLines as $index => $rLine) {
					if (preg_match("/".$delimitor."/", $rLine)) {
						//next line is the prompt
						if (preg_match("/>>>>(.*?)<<<</", $rLines[$index + 1], $rawPrompt)) {
							//got the right prompt
							break;
						} else {
							throw new \Exception(__METHOD__ . ">> Failed to set shell prompt value");
						}
					}
				}
				
				//shell is now usable
				
				//just a tiny sleep (1 ms) to make sure the last command has completed its prompt
				//this seems to only be needed on Arch, but i imagine that other busy servers will encounter the same issue.
				usleep(1000);
				$columnCount	= $this->getTerminalWidth();
				$repeatChar		= "A";
				$repeatCount	= $columnCount * 2;
				
				$strCmd			= "echo \"".str_repeat($repeatChar, $repeatCount)."\"";
				$reData			= $this->exeCmd($strCmd, null, null);
				
				$regEx			= "echo \"([".$repeatChar."]+)([^".$repeatChar."]+)([".$repeatChar."]+)";		
				if (preg_match("/".$regEx."/", $reData, $breakerRaw)) {
					//if the result for the previous command did not end in a line break, then the terminal will
					//introduce a 200d (hex) terminal break rather than the normal 2008 (hex) break for the next command. not sure why
					$this->_termBreakDetail[]		= $breakerRaw[2];
					$this->_termBreakDetail[]		= " \r";
				} else {
					throw new \Exception(__METHOD__ . ">> Failed to determine terminal break detail.");
				}
	
				if ($this->getParentShell() === null) {
					//if there is no parent then this is the initial shell
					//get the PID of the parent so we can kill that process if everything else fails.
					$strCmd			= "(cat /proc/$$/status | grep PPid)";
					$reData			= $this->exeCmd($strCmd, null, null);
	
					if (preg_match("/([0-9]+)/", $reData, $rawPPID)) {
						$this->_baseShellPPID	= $rawPPID[1];
					} else {
						throw new \Exception(__METHOD__ . ">> Failed to get parent process id");
					}
				}

				//reset the output so we have a clean beginning
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
				
			$errObj	= null;
			try {
		
		
				if ($this->getInitialized() !== true) {
					//in case the shell was setup, but no commands were
					//issued, we will need to initiate before terminating
					$this->shellInitialize();
				}
		
				//make sure the last command is dead
				$this->killLastProcess();
				
				//issue the exit
				$strCmd		= "exit";
				$delimitor	= "(screen is terminating)|(logout)";
				$this->exeCmd($strCmd, $delimitor);
		
			} catch (\Exception $e) {
					
				switch($e->getCode()){
					default;
					$errObj	= $e;
				}
			}

			if ($errObj !== null) {
				//something went wrong, try force killing the process
				try {
					
					if ($this->_baseShellPPID !== null) {
						
						if ($this->debug === true) {
							$this->addDebugData("Sending SIGTERM to process PID: " . $this->_baseShellPPID);
						}
						//if we are the base shell try to forcefully kill
						\MTS\Factories::getActions()->getLocalProcesses()->sigTermPid($this->_baseShellPPID);
						//success problem handled
					}
					
					$this->_initialized	= false;

				} catch (\Exception $e) {
					switch($e->getCode()){
						default;
						throw $errObj;
					}
				}
			} else {
				$this->_initialized	= false;
			}
		}
	}
	protected function shellKillLastProcess()
	{
		if ($this->getInitialized() !== null && $this->getInitialized() !== false) {
	
			//SIGINT current process and get prompt
			$strCmd		= chr(3) . $this->_strCmdCommit;
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