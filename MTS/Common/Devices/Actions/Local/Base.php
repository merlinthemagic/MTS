<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Local;

class Base
{
	protected $_classStore=array();
	
	protected function shellExec($cmdString)
	{
		exec($cmdString, $rData);
		$cReturn	= implode("\n", $rData);
		return $cReturn;
	}
	
	protected function getLocalOsObj()
	{
		if (array_key_exists('localOsObj', $this->_classStore) === false) {
			$this->_classStore['localOsObj']	= \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
		}
		return $this->_classStore['localOsObj'];
	}
}