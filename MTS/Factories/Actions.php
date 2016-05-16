<?php
//© 2016 Martin Madsen
namespace MTS\Factories;

class Actions
{
	private $_classStore=array();
	
	//local actions
	public function getLocalOperatingSystem()
	{
		if (array_key_exists(__METHOD__, $this->_classStore) === false) {
			$this->_classStore[__METHOD__]	= new \MTS\Common\Devices\Actions\Local\Host\OperatingSystem();
		}
		return $this->_classStore[__METHOD__];
	}
}