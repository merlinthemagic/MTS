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

	public function __construct()
	{
		$this->_shellPrompt		= "[" . uniqid("bash.", true) . "]";
		$this->_strCmdCommit	= chr(13);
		$this->_cmdMaxTimeout	= (ini_get('max_execution_time') - 0.5) * 1000;
	}
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
	protected function shellStrExecute($strCmd, $delimitor, $maxTimeout)
	{
		if ($this->getInitialized() !== true) {
			$this->shellInitialize();
		}
		if ($maxTimeout === null) {
			$maxTimeout		= $this->_cmdMaxTimeout;
		}
		
		if ($delimitor === null) {
			$delimitorProvided	= false;
			$delimitor		= preg_quote($this->_shellPrompt);
		} else {
			$delimitorProvided	= true;
		}
		
		//make sure nothing if left over from last command
		$this->getPipes()->resetReadPosition();
		
		$rawCmdStr	= $strCmd . $this->_strCmdCommit;
		$wData		= $this->shellWrite($rawCmdStr);
		if (strlen($wData['error']) > 0) {
			throw new \Exception(__METHOD__ . ">> Failed to write command. Error: " . $wData['error']);
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
						$expectCmd			= str_replace($this->_termBreakDetail['breakSeq'], "", $lines[0]);
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
			
			//set the prompt to a known value
			$strCmd		= "PS1=\"".$this->_shellPrompt."\"" . $this->_strCmdCommit;
			$wData		= $this->shellWrite($strCmd);
			if (strlen($wData['error']) > 0) {
				$this->shellTerminate();
				throw new \Exception(__METHOD__ . ">> Failed to write shell promt command. Error: " . $wData['error']);
			}
			
			//see if it took hold
			$strCmd		= $this->_strCmdCommit . "echo \">>>>\"\$PS1\"<<<<\" && echo $$\"MERLIN\"$$"  . $this->_strCmdCommit;
			$wData		= $this->shellWrite($strCmd);
			if (strlen($wData['error']) > 0) {
				$this->shellTerminate();
				throw new \Exception(__METHOD__ . ">> Failed to write shell promt validation command. Error: " . $wData['error']);
			}
			
			$delimitor	= "[0-9]+MERLIN[0-9]+";
			$rData		= $this->shellRead($delimitor, $this->_cmdMaxTimeout);
			if (strlen($rData['error']) > 0) {
				$this->shellTerminate();
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
						$this->shellTerminate();
						throw new \Exception(__METHOD__ . ">> Failed to set shell prompt value");
					}
				}
			}
			
			//make sure nothing if left over from last command
			$this->getPipes()->resetReadPosition();
			
			//how wide is the terminal window we are working with
			$strCmd		= "echo \$COLUMNS && echo $$\"DUNBAR\"$$" . $this->_strCmdCommit;
			$wData		= $this->shellWrite($strCmd);
			if (strlen($wData['error']) > 0) {
				$this->shellTerminate();
				throw new \Exception(__METHOD__ . ">> Failed to get write column count command. Error: " . $wData['error']);
			}
				
			$delimitor	= "[0-9]+DUNBAR[0-9]+";
			$rData		= $this->shellRead($delimitor, $this->_cmdMaxTimeout);
			if (strlen($rData['error']) > 0) {
				$this->shellTerminate();
				throw new \Exception(__METHOD__ . ">> Failed to read column count command. Error: " . $rData['error']);
			}
			
			$columnCount	= null;
			$rLines	= array_reverse(explode("\n", $rData['data']));
			foreach ($rLines as $index => $rLine) {
				if (preg_match("/".$delimitor."/", $rLine)) {
					//next line is the prompt
					if (preg_match("/([0-9]+)/", $rLines[$index + 1], $rawPrompt)) {
						//got the column count
						$columnCount	= $rawPrompt[1];
						break;
					}
				}
			}
			
			if ($columnCount === null) {
				$this->shellTerminate();
				throw new \Exception(__METHOD__ . ">> Failed to find column count");
			}
			
			//make sure nothing if left over from last command
			$this->getPipes()->resetReadPosition();
			
			//the terminal window will break up long commands. NOT the return but the actual command
			//we need to know what that break looks like so we can filter the command itself from the return
			$repeatChar		= "A";
			$repeatCount	= $columnCount * 2;

			$strCmd		= "echo \"".str_repeat($repeatChar, $repeatCount)."\" && echo $$\"MERLIN\"$$" . $this->_strCmdCommit;
			$wData		= $this->shellWrite($strCmd);
			if (strlen($wData['error']) > 0) {
				$this->shellTerminate();
				throw new \Exception(__METHOD__ . ">> Failed to get write terminal break test command. Error: " . $wData['error']);
			}
			
			$delimitor	= "[0-9]+MERLIN[0-9]+";
			$rData		= $this->shellRead($delimitor, $this->_cmdMaxTimeout);
			if (strlen($rData['error']) > 0) {
				$this->shellTerminate();
				throw new \Exception(__METHOD__ . ">> Failed to read terminal break test command. Error: " . $rData['error']);
			}

			$regEx			= "echo \"([".$repeatChar."]+)([^".$repeatChar."]+)([".$repeatChar."]+)";
			preg_match("/".$regEx."/", $rData['data'], $breakerRaw);
			
			if (array_key_exists(2, $breakerRaw) === false) {
				$this->shellTerminate();
				throw new \Exception(__METHOD__ . ">> Failed to determine terminal break detail.");
			}
			
			$this->_termBreakDetail['charCount']	= strlen($breakerRaw[1]);
			
			//if the result for the previous command did not end in a line break, then the terminal will
			//introduce a 200d (hex) terminal break rather than the normal 2008 (hex) break for the next command. not sure why				
			$this->_termBreakDetail['breakSeq'][]		= $breakerRaw[2];
			$this->_termBreakDetail['breakSeq'][]		= " \r";
			
			//reset the output, shell is now initialized
			$this->getPipes()->resetReadPosition();
			
			$this->_initialized 			= true;
		} elseif ($this->getInitialized() === false) {
			throw new \Exception(__METHOD__ . ">> Error. Shell has been terminated, cannot initiate");
		}
	}
	protected function shellTerminate()
	{
		if ($this->getInitialized() === true || $this->getInitialized() == "setup") {
			$this->shellKillLastProcess();
			
			$strCmd		= "exit" . $this->_strCmdCommit;
			$wData		= $this->shellWrite($strCmd);
			if (strlen($wData['error']) > 0) {
				throw new \Exception(__METHOD__ . ">> Failed to write shell termination command. Error: " . $wData['error']);
			}
			$delimitor	= "(screen is terminating)|(exit)";
			$rData		= $this->shellRead($delimitor, $this->_cmdMaxTimeout);
			if (strlen($rData['error']) > 0) {
				throw new \Exception(__METHOD__ . ">> Failed to receive shell termination result. Error: " . $rData['error']);
			}
			$this->_initialized	= false;
		}
	}
	protected function shellKillLastProcess()
	{
		if ($this->getInitialized() !== null || $this->getInitialized() !== false) {

			//SIGINT current process
			$strCmd		= chr(3);
			$wData		= $this->shellWrite($strCmd);
		
			$strCmd		= $this->_strCmdCommit . "echo $$\"KILLLAST\"$$"  . $this->_strCmdCommit;
			$wData		= $this->shellWrite($strCmd);
			if (strlen($wData['error']) > 0) {
				throw new \Exception(__METHOD__ . ">> Failed to write command that would kill last process. Error: " . $wData['error']);
			}
				
			//let the process exit, this can take time. Append the delimitor since it must be end of line
			//after ^C is issued
			$delimitor	= "[0-9]+KILLLAST[0-9]+";
			$rData		= $this->shellRead($delimitor, $this->_cmdMaxTimeout);
			if (strlen($rData['error']) > 0) {
				throw new \Exception(__METHOD__ . ">> Failed to kill last process. Error: " . $rData['error']);
			}
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
		return $return;
	}
	private function shellRead($regex=false, $maxWaitMs=0)
	{
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
		return $return;
	}
}