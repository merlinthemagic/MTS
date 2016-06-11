<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Browsers;

class Base
{
	public $debug=false;
	public $debugData=array();
	protected $_initialized=null;
	protected $_windowObjs=array();

	public function __construct()
	{
		//on uncaught exception __destruct is not called, this might leave the browser as a zombie running on the system we cant have that.
		register_shutdown_function(array($this, '__destruct'));
	}
	public function __destruct()
	{
		$this->terminate();
	}
	public function setDebug($bool)
	{
		$this->debug	= $bool;
	}
	public function addDebugData($debugData)
	{
		$this->debugData[]	= $debugData;
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
	}
	public function initialize()
	{
		$this->browserInitialize();
	}
	public function terminate()
	{
		$this->browserTerminate();
	}
	public function getInitialized()
	{
		return $this->_initialized;
	}
}