<?php
//© 2016 Martin Madsen
namespace MTS\Factories;

class Time
{
	protected $_classStore=array();
	
	public function getEpochTool()
	{
		if (array_key_exists(__METHOD__, $this->_classStore) === false) {
			$this->_classStore[__METHOD__]	= new \MTS\Common\Tools\Time\Epoch();
		}
		return $this->_classStore[__METHOD__];
	}
}