<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Shells;

class RouterOS extends Base
{
	private $_procPipe=null;
	private $_strCmdCommit=null;
	private $_cmdSigInt=null;
	private $_cmdMaxTimeout=null;
	private $_baseShellPPID=null;
	public $columnCount=80;
	
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
	public function getTerminalWidth()
	{
		return $this->columnCount;
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
		
		$stdDelimitor	= false;
		if ($delimitor === null) {
			$stdDelimitor	= true;
			$delimitor		= preg_quote($this->_shellPrompt);
		}
		
		$rTimeout	= $this->getMaxExecutionTime();
		if ($this->_terminating === true || $this->getInitialized() === 'setup') {
			//when terminating and setting up we should be able to take a very long time
			$maxTimeout		= 15000;
		} elseif ($maxTimeout === null) {
			$maxTimeout		= $rTimeout;
		} elseif ($maxTimeout > $rTimeout) {
			throw new \Exception(__METHOD__ . ">> You must set a lower timeout value, the current max allowed is: " . $rTimeout . ", that is what remains of PHP max_execution_time");
		}
		
		
		if ($strCmd === false) {
			//we only want to read
		} else {
			$this->getPipes()->resetReadPosition();
			$rawCmdStr	= $strCmd . $this->_strCmdCommit;
			$wData		= $this->shellWrite($rawCmdStr);
			if (strlen($wData['error']) > 0) {
				throw new \Exception(__METHOD__ . ">> Failed to write command submit. Error: " . $wData['error']);
			}
		}
		
		if ($maxTimeout === false || $maxTimeout == 0) {
			//no return requested
		} else {
				
			$rData	= $this->shellRead($delimitor, $maxTimeout);
			if ($delimitor !== false && strlen($rData['error']) > 0) {
				//we did not find the delimitor in the allowed time frame
		
				if ($rData['error'] == "timeout") {
					//need a code for this common occuring error, used for i.e. ssh connect times out
					throw new \Exception(__METHOD__ . ">> Read data timeout", 2500);
				} else {
					throw new \Exception(__METHOD__ . ">> Failed to read data. Error: " . $rData['error']);
				}
					
			} else {
					
				$rawData			= $rData['data'];
		
				if ($this->getInitialized() === true) {
					$lines				= explode("\n", $rawData);
					$lineCount			= count($lines);
					if ($lineCount > 0) {
		
						//Command string removal from return
						if ($strCmd !== false) {
							$strCmdLen		= strlen(trim($strCmd));
							if ($strCmdLen > 0) {
								//there could be junk left over on the terminal before the command was issued
								//so allow a longer string to match before giving up
								$strCmdmaxLen	= ($strCmdLen * 3);
								$cmdLine		= "";
								foreach ($lines as $lKey => $line) {
									$cmdLine	.= trim($line);
									$cmdLineLen	= strlen($cmdLine);
									if ($cmdLineLen > 0) {
										if ($cmdLineLen == ($strCmdLen + strpos($cmdLine, $strCmd))) {
											//found the command, delete the lines that has the command and anything before it
											$lines		= array_slice($lines, ($lKey + 1));
											break;
										} elseif ($cmdLineLen > $strCmdmaxLen) {
											//no match
											break;
										}
									}
								}
							}
						} else {
							//this is a read without a command being issued
						}
		
						//Locate the delimitor in the return
						if ($delimitor !== false) {
							//its faster to start from the bottom of the return
							$lines		= array_reverse($lines);
							foreach ($lines as $lKey => $line) {
								if (preg_match("/(.*?)?(".$delimitor.")/", $line, $lineParts)) {
									if ($stdDelimitor === false) {
										//User provided the delimitor, we include the data from it
										$lines[$lKey]	= $lineParts[1] . $lineParts[2];
									} else {
										//standard delimitor, remove it
										$preDelimLen	= strlen(trim($lineParts[1]));
										if ($preDelimLen > 0) {
											$lines[$lKey]	= $lineParts[1];
										} else {
											//Only delimitor on the last line
											unset($lines[$lKey]);
										}
									}
									break;
										
								} else {
									//this is data that was picked up after the delimitor was reached
									unset($lines[$lKey]);
								}
							}
							$lines		= array_reverse($lines);
						}
		
						$rawData		= implode("\n", $lines);
		
					}
					unset($lines);
				}
		
				return $rawData;
			}
		}
	}
	protected function shellInitialize()
	{
		if ($this->getInitialized() === null) {
			$this->_initialized		= 'setup';
			
			try {
				
				//set the variables
				$this->_strCmdCommit				= chr(13);
				$this->_cmdSigInt					= chr(3) . $this->_strCmdCommit;
				$promptReturn						= $this->exeCmd("", "\[(([a-zA-Z0-9\_\-]+)@([a-zA-Z0-9\_\-]+))]\s+\>");

				//prompt may carry some junk back, not sure why
				$singlePrompts			= array_filter(explode("\n", $promptReturn));
				foreach ($singlePrompts as $singlePrompt) {
					$singlePrompt	= trim($singlePrompt);
					if (preg_match("/(\[(([a-zA-Z0-9\_\-]+)@([a-zA-Z0-9\_\-]+))]\s+\>)/", $singlePrompt, $promptParts) == 1) {
						$this->_shellPrompt	= $promptParts[1];
						break;
					}
				}
				
				if ($this->_shellPrompt === null) {
					throw new \Exception(__METHOD__ . ">> Failed to get shell prompt");
				}
				
				//for unknown reasons the prompt is sometimes written more than once initially
				//that means the first real command is offset and receives no return
				$testEnd		= (time() + 20);
				$wDone			= false;
				$i=0;
				while ($wDone === false) {
					$i++;
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
							//wait for output to clear, sleep longer and longer or we just clutter the pipe
							//on slow connections
							if ($i == 1) {
								usleep(250000);
							} elseif ($i == 2) {
								usleep(500000);
							} elseif ($i == 3) {
								usleep(750000);
							} else {
								sleep(1);
							}
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
				$delimitor	= "(".preg_quote($this->getParentShell()->getShellPrompt()).")";
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