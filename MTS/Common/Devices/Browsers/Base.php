<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Browsers;

class Base
{
	protected $_keepAlive=false;
	protected $_procPID=null;
	protected $_initialized=null;
	protected $_windowObjs=array();
	protected $_debug=false;
	protected $_debugData=array();
	protected $_defaultExecutionTime=30000;
	
	public function __construct()
	{
		//on uncaught exception __destruct is not called, this might leave the browser as a zombie running on the system we cant have that.
		register_shutdown_function(array($this, '__destruct'));
	}
	public function __destruct()
	{
		if ($this->_keepAlive !== true) {
			$this->terminate();
		}
	}
	public function setDebug($bool)
	{
		if ($this->_debug != $bool) {
			$this->_debug	= $bool;
			$this->browserSetDebug();
		}
	}
	public function getDebug()
	{
		return $this->_debug;
	}
	public function addDebugData($debugData)
	{
		$this->_debugData[]	= $debugData;
	}
	public function getDebugData()
	{
		return $this->_debugData;
	}
	public function setKeepalive($bool)
	{
		$this->_keepAlive	= $bool;
	}
	public function getNewWindow($url=null, $width=null, $height=null)
	{
		$newWindow	= new \MTS\Common\Devices\Browsers\Window();
		$newWindow->setBrowser($this);
		if ($width !== null && $height !== null) {
			$newWindow->setSize($width, $height);
		}
		if ($url !== null) {
			$newWindow->setURL($url);
		}
		$this->_windowObjs[]	= $newWindow;
		return $newWindow;
	}
	public function getWindows()
	{
		return $this->_windowObjs;
	}
	public function getWindow($uuid)
	{
		$windowObjs	= $this->getWindows();
		foreach ($windowObjs as $windowObj) {
			if ($windowObj->getUUID() == $uuid) {
				return $windowObj;
			}
		}
		return false;
	}
	public function closeWindow($windowObj)
	{
		$children	= $windowObj->getChildren();
		$childCount	= count($children);
		if ($childCount > 0) {
			foreach ($children as $child) {
				$this->closeWindow($child);
			}
		}
		
		$this->browserCloseWindow($windowObj);
		
		$winObjs	= $this->getWindows();
		foreach ($winObjs as $index => $winObj) {
			if ($winObj->getUUID() == $windowObj->getUUID()) {
				$parentObj	= $winObj->getParent();
				if ($parentObj !== null) {
					$parentObj->removeChild($winObj);
				}
				unset($this->_windowObjs[$index]);
				break;
			}
		}
	}
	public function initialize()
	{
		$this->browserInitialize();
	}
	public function terminate()
	{
		if ($this->getInitialized() !== "terminating" && $this->getInitialized() !== false) {

			if ($this->getDebug() === true) {
				$exeTimeout		= \MTS\Factories::getActions()->getLocalPhpEnvironment()->getRemainingExecutionTime();
				if ($exeTimeout == 0) {
					//help debug when commands fail because the "max_execution_time" was not long enough
					$this->addDebugData("Process terminated because 'max_execution_time' value: ".ini_get('max_execution_time').", was exceeded.");
				}
			}
			
			//PHP does not allow us to handle exceptions during shutdown. If termination fails for some reason
			//the process may hang around taking up resources on the server. The only way to avoid this is to
			//have a kill wait around to see if terminate completes its job. if not force the process termination
			if ($this->getProcessPID() !== null) {
				\MTS\Factories::getActions()->getLocalProcesses()->sigTermPid($this->getProcessPID(), 15);
			}
			
			//terminate the browser
			$this->browserTerminate();
		}
	}
	public function setDefaultExecutionTime($mSecs)
	{
		$this->_defaultExecutionTime	= intval($mSecs);
	}
	public function getDefaultExecutionTime()
	{
		return $this->_defaultExecutionTime;
	}
	public function getInitialized()
	{
		return $this->_initialized;
	}
	public function getProcessPID()
	{
		return $this->_procPID;
	}
}