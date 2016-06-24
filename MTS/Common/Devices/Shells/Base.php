<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Shells;

class Base
{
	
	protected $_childShell=null;
	protected $_parentShell=null;
	protected $_initialized=null;
	protected $_shellPrompt=null;
	protected $_shellUUID=null;
	protected $_baseShellPPID=null;
	protected $_debug=false;
	protected $_debugData=array();
	
	public function __construct()
	{
		//on uncaught exception __destruct is not called, this leaves the shell as a zombie running on the system we cant have that.
		register_shutdown_function(array($this, '__destruct'));
	}
	public function __destruct()
	{
		if ($this->getParentShell() === null) {
			//destruction should only be triggered in the initial shell.
			//that way we get an orderly shutdown of nested shells
			$this->terminate();
		}
	}
	public function setDebug($bool)
	{
		$this->_debug	= $bool;
		//make sure the entire chell chain has the same debug. 
		if ($this->getChildShell() !== null && $this->getChildShell()->getDebug() !== $bool) {
			$this->getChildShell()->setDebug($bool);
		}
		if ($this->getParentShell() !== null && $this->getParentShell()->getDebug() !== $bool) {
			$this->getParentShell()->setDebug($bool);
		}
	}
	public function getDebug()
	{
		return $this->_debug;
	}
	public function addDebugData($debugData)
	{
		$parentShell		= $this->getParentShell();
		if ($parentShell === null) {
			$this->_debugData[]	= $debugData;
		} else {
			$parentShell->addDebugData($debugData);
		}
	}
	public function getDebugData()
	{
		$parentShell		= $this->getParentShell();
		if ($parentShell === null) {
			return $this->_debugData;
		} else {
			return $parentShell->getDebugData();
		}
	}
	
	public function exeCmd($strCmd, $delimitor=null, $timeout=null)
	{
		//$strCmd: string command to execute
		
		//$delimitor: regex when matched ends the command and returns data.
		//defaults to a custom shell prompt determined by the shell class
		//You should only override the default if the command does not end in a regular prompt, or you want only a partial return from the command.
		//If you do not want to use a delimitor at all set to false, this will force a read until $timeout is exceeded

		//$timeout: the absolute longest the command is allowed to run
		//set to 0 if you do not wish the receive a return from the command
		//You should only override if a command takes a very long time or for a command that continues to return data
		//i.e ping. Without a timeout on a ping the command would never finish and return
		//default is determined by the shell class
		
		//if you know a command will continue to return output and hold up the shell
		//you should issue another command to stop it or use the killLastProcess() function

		try {

			if ($this->getChildShell() !== null) {
				//must execute on child as it rides on top of this shell
				return $this->getChildShell()->exeCmd($strCmd, $delimitor, $timeout);
			} else {
				return $this->shellStrExecute($strCmd, $delimitor, $timeout);
			}
			
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function killLastProcess()
	{
		if ($this->getChildShell() !== null) {
			//must execute on child as it rides on top of this shell
			$this->getChildShell()->killLastProcess();
		} else {
			$this->shellKillLastProcess();
		}
	}
	public function terminate()
	{
		if ($this->getParentShell() === null) {
			
			//PHP does not allow us to handle exceptions during shutdown. If termination fails on the base shell
			//it will hang around forever taking up resources on the server. The only way to avoid this is to
			//have a kill wait around to see if terminate completes its job. if not force the process termination
			if ($this->getBaseShellPID() !== null) {
				\MTS\Factories::getActions()->getLocalProcesses()->sigTermPid($this->getBaseShellPID(), 15);
			}
			
			if ($this->getDebug() === true) {
				$this->addDebugData("Starting Termination of shells. If you dont see a 'Completed' message something went wrong");
				if ($this->getMaxExecutionTime() == 0) {
					//help debug when commands fail when "max_execution_time" was not long enough.
					$this->addDebugData("Shell terminated because 'max_execution_time': ".ini_get('max_execution_time').", was reached.");
				}
			}
		}

		$childError	= null;
		$ownError	= null;
		try {
			//child shells must be shutdown before this
			if ($this->getChildShell() !== null) {
				$this->getChildShell()->terminate();
			}
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				$childError = $e;
			}
		}
		
		try {
			
			$this->shellTerminate();
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				$ownError = $e;
			}
		}

		//tell the parent we are shutdown
		$parentShell	= $this->getParentShell();
		if ($this->getParentShell() !== null) {
			$this->getParentShell()->setChildShell(null);
			//clean up
			$this->getParentShell()->exeCmd("");
		}
		
		//finish by throwing the errors. It is crucial that the parent
		//is informed it no longer has a child, even though it may have failed 
		//some part of the termination process. otherwise commands are still passed upstream
		//and the initial shell will never get a chance to terminate
		if ($childError !== null) {
			throw $childError;
		} elseif ($ownError !== null) {
			throw $ownError;
		}
		
		if ($this->getDebug() === true && $this->getParentShell() === null) {
			$this->addDebugData("Completed Termination of shells");
		}
		
		if ($parentShell !== null) {
			//the user may still have some use of the parent
			return $parentShell;
		} else {
			return null;
		}
	}
	public function setChildShell($shellObj)
	{
		if ($shellObj === null) {
			//this is a child destructing it self and letting its parent know it is done
			$this->_childShell = null;
		} else {
			if ($this->getChildShell() !== null) {
				$this->getChildShell()->setChildShell($shellObj);
			} else {
				$this->_childShell 			= $shellObj;
				$this->_childShell->setDebug($this->getDebug());
				$this->_childShell->setParentShell($this);
			}
		}
	}
	public function getChildShell()
	{
		return $this->_childShell;
	}
	public function getActiveShell()
	{
		if ($this->getChildShell() === null) {
			return $this;
		} else {
			return $this->getChildShell()->getActiveShell();
		}
	}
	public function setParentShell($shellObj)
	{
		//this should only be set by the parent shell itself
		$this->_parentShell	= $shellObj;
	}
	public function getParentShell()
	{
		return $this->_parentShell;
	}
	public function getInitialized()
	{
		return $this->_initialized;
	}
	public function getShellPrompt()
	{
		//needed on exit from child shell
		return $this->_shellPrompt;
	}
	public function getShellUUID()
	{
		if ($this->_shellUUID === null) {
			$this->_shellUUID		= uniqid("", true);
		}
		return $this->_shellUUID;
	}
	public function getMaxExecutionTime()
	{
		//returns time until max_execution_time is exceeded
		$curRunTime		= (\MTS\Factories::getTime()->getEpochTool()->getCurrentMiliTime() - MTS_EXECUTION_START);
		$cmdMaxTimeout	= floor((ini_get('max_execution_time') - $curRunTime) * 1000);
	
		if ($cmdMaxTimeout < 0) {
			$cmdMaxTimeout = 0;
		}
	
		return $cmdMaxTimeout;
	}
	public function getBaseShellPID()
	{
		if ($this->getParentShell() !== null) {
			return $this->getParentShell()->getBaseShellPID();
		} else {
			return $this->_baseShellPPID;
		}
	}
}