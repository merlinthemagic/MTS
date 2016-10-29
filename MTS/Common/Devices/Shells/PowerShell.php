<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Shells;

class PowerShell extends Base
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
			$delimitor		= $this->_shellPrompt;
		} else {
			//for now we are using json encoded commands so the delimitor is fixed
			$stdDelimitor	= true;
			$delimitor		= $this->_shellPrompt;
		}

		if ($maxTimeout === null) {
			$maxTimeout		= $this->getDefaultExecutionTime();
		}
		
		if ($strCmd === false) {
			//we only want to read
		} else {
			$this->getPipes()->resetReadPosition();
			$rawCmdStr	= $strCmd;
			$wData		= $this->shellWrite($rawCmdStr, $maxTimeout);
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
					
				$rawData	= $rData['data'];
				
				$sStr		= "cmdReturnStart>>>";
				$sPos		= strpos($rawData, $sStr);
				$iPos		= $sPos + strlen($sStr);
				$eStr		= "<<<cmdReturnEnd";
				$ePos		= strpos($rawData, $eStr);
					
				$rLen		= ($ePos - $iPos);
				$encReturn	= substr($rawData, $iPos, $rLen);
				$json		= base64_decode($encReturn);
				$jsonArr	= json_decode($json, true);
				
				//decode the data and return it
				return base64_decode($jsonArr["result"]["data"]);
			}
		}
	}
	protected function shellInitialize()
	{
		if ($this->getInitialized() === null) {
			$this->_initialized		= 'setup';
			
			try {

				$this->_shellPrompt	= "cmdReturnStart\>\>\>(.*)\<\<\<cmdReturnEnd";
				
				$strCmd	= "Get-Process -id \$pid";
				$rData	= $this->exeCmd($strCmd);
				if(preg_match("/([0-9]+)\s+powershell/i", $rData, $rawPid) == 1) {
					$this->_procPID	= $rawPid[1];
				} else {
					throw new \Exception(__METHOD__ . ">> Failed to get Process PID during Initialize");
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
				$this->_initialized	= "terminating";
				
				//make sure the last command is dead
				$this->killLastProcess();

				//issue the exit
				$strCmd		= "mtsTerminate";
				$this->exeCmd($strCmd);
				
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
			//not working currently because not real shell
		}
	}
	private function shellWrite($strCmd, $timeout=null)
	{
		//currently the powerShell is not in a terminal so the command structure is different
		//the goal is to move off the script and into a real terminal without winPTY or the like
		$cmdArr						= array();
		$cmdArr['cmd']				= array();
		$cmdArr['cmd']['id']		= uniqid();
		$cmdArr['cmd']['timeout']	= $timeout;
		$cmdArr['cmd']['string']	= $strCmd;
		
		$cmdJson	= json_encode($cmdArr, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);
		$wStr		=  "cmdStart>>>" . base64_encode($cmdJson) . "<<<cmdEnd";
		
		$return['error']	= null;
		$return['stime']	= \MTS\Factories::getTime()->getEpochTool()->getCurrentMiliTime();
		try {
			$this->getPipes()->strWrite($wStr);
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
				} else {
					//wait for a tiny bit no need to saturate the CPU
					usleep(10000);
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