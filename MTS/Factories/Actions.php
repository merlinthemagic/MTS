<?php
//© 2016 Martin Madsen
namespace MTS\Factories;

class Actions
{
	protected $_classStore=array();
	
	//local actions
	public function getLocalOperatingSystem()
	{
		if (array_key_exists(__METHOD__, $this->_classStore) === false) {
			$this->_classStore[__METHOD__]	= new \MTS\Common\Devices\Actions\Local\Host\OperatingSystem();
		}
		return $this->_classStore[__METHOD__];
	}
	public function getLocalApplicationPaths()
	{
		if (array_key_exists(__METHOD__, $this->_classStore) === false) {
			$this->_classStore[__METHOD__]	= new \MTS\Common\Devices\Actions\Local\Host\ApplicationPaths();
		}
		return $this->_classStore[__METHOD__];
	}
	public function getLocalProcesses()
	{
		if (array_key_exists(__METHOD__, $this->_classStore) === false) {
			$this->_classStore[__METHOD__]	= new \MTS\Common\Devices\Actions\Local\Host\Processes();
		}
		return $this->_classStore[__METHOD__];
	}
	public function getLocalShell()
	{
		if (array_key_exists(__METHOD__, $this->_classStore) === false) {
			$this->_classStore[__METHOD__]	= new \MTS\Common\Devices\Actions\Local\Host\Shell();
		}
		return $this->_classStore[__METHOD__];
	}
	public function getLocalBrowser()
	{
		if (array_key_exists(__METHOD__, $this->_classStore) === false) {
			$this->_classStore[__METHOD__]	= new \MTS\Common\Devices\Actions\Local\Host\Browser();
		}
		return $this->_classStore[__METHOD__];
	}
	public function getLocalPhpEnvironment()
	{
		if (array_key_exists(__METHOD__, $this->_classStore) === false) {
			$this->_classStore[__METHOD__]	= new \MTS\Common\Devices\Actions\Local\Host\PhpEnvironment();
		}
		return $this->_classStore[__METHOD__];
	}
	public function getLocalUsers()
	{
		if (array_key_exists(__METHOD__, $this->_classStore) === false) {
			$this->_classStore[__METHOD__]	= new \MTS\Common\Devices\Actions\Local\Host\Users();
		}
		return $this->_classStore[__METHOD__];
	}
	
	
	//remote actions
	public function getRemoteUsers()
	{
		if (array_key_exists(__METHOD__, $this->_classStore) === false) {
			$this->_classStore[__METHOD__]	= new \MTS\Common\Devices\Actions\Remote\Host\Users();
		}
		return $this->_classStore[__METHOD__];
	}
	public function getRemoteOperatingSystem()
	{
		if (array_key_exists(__METHOD__, $this->_classStore) === false) {
			$this->_classStore[__METHOD__]	= new \MTS\Common\Devices\Actions\Remote\Host\OperatingSystem();
		}
		return $this->_classStore[__METHOD__];
	}
	public function getRemoteConnectionsSsh()
	{
		if (array_key_exists(__METHOD__, $this->_classStore) === false) {
			$this->_classStore[__METHOD__]	= new \MTS\Common\Devices\Actions\Remote\Connections\Ssh();
		}
		return $this->_classStore[__METHOD__];
	}
}