<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Shells;

class Bash extends Base
{
	private $_procPipe=null;
	private $_strCmdCommit=null;
	private	$_cmdSigInt=null;
	public	$columnCount=null;

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
		$strCmd		= "echo \$COLUMNS";
		$reData		= $this->exeCmd($strCmd);
		if (preg_match("/([0-9]+)/", $reData, $rawColCount)) {
			$this->columnCount	=  $rawColCount[1];
		} else {
			throw new \Exception(__METHOD__ . ">> Failed to get terminal width");
		}
		
		return $this->columnCount;
	}
	protected function shellStrExecute($strCmd, $delimitor, $maxTimeout)
	{
		//this method should only be called from base::exeCmd()
		//if this method is called directly it breaks the child shell logic
		//all commands must be executed on the furthest child
		if ($this->getInitialized() === true || $this->getInitialized() === 'terminating') {
			//no problem
		} elseif ($this->getInitialized() === false) {
			throw new \Exception(__METHOD__ . ">> Error. Shell has been terminated, cannot execute anymore commands.");
		} elseif ($this->getInitialized() !== true) {
			$this->shellInitialize();
		}
		
		$stdDelimitor	= false;
		if ($delimitor === null) {
			$stdDelimitor	= true;
			$delimitor		= preg_quote($this->_shellPrompt);
		}

		if ($maxTimeout === null) {
			$maxTimeout		= $this->getDefaultExecutionTime();
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
				$this->_shellPrompt		= "[" . uniqid("bash.", true) . "]";
				$this->_strCmdCommit	= chr(13);
				$this->_cmdSigInt		= chr(3) . $this->_strCmdCommit;
			
				//set the prompt to a known value
				$strCmd		= "PS1=\"".$this->_shellPrompt."\"";
				$delimitor	= "(\n" . preg_quote($this->_shellPrompt) .")";
				$this->exeCmd($strCmd, $delimitor);
				
				//shell is now usable with the standard delimitor

				if ($this->getParentShell() === null) {
					//if there is no parent then this is the initial shell
					//get the PID of the parent so we can kill that process if everything else fails.
					$strCmd			= "(cat /proc/$$/status | grep PPid)";
					$ppData			= $this->exeCmd($strCmd);

					if (preg_match("/\s([0-9]+)/", $ppData, $rawPPID) == 1) {
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
					$this->terminate();
					throw $e;
				}
			}
		}
	}
	protected function shellTerminate()
	{
		if ($this->getInitialized() !== false) {

			try {

				//in case the shell was setup, but no commands were issued, we will need to initiate before terminating
				//the method is safe to run since it ignores if already executed
				$this->shellInitialize();
				
				//we cannot set status terminating until the init has completed
				$this->_initialized	= 'terminating';
				
				//make sure the last command is dead
				$this->killLastProcess();

				//issue the exit
				$strCmd		= "exit";
				
				if ($this->getParentShell() === null) {
					$delimitor	= "(screen is terminating)";
				} else {
					$delimitor	= "(".preg_quote($this->getParentShell()->getShellPrompt()).")";
				}
				
				$this->exeCmd($strCmd, $delimitor);
				
				$this->_initialized	= false;
				
			} catch (\Exception $e) {
				switch($e->getCode()) {
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
		
		if ($this->getDebug() === true) {
			
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
		
		if ($this->getDebug() === true) {
			$debugData				= $return;
			$debugData['type']		= __FUNCTION__;
			$debugData['regex']		= $regex;
			$debugData['timeout']	= $maxWaitMs;
			$this->addDebugData($debugData);
		}
		return $return;
	}
}