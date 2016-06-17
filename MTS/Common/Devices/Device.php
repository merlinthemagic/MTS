<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices;

class Device
{
	protected $_debug=false;
	protected $_classStore=array();
	protected $_shellObj=null;
	protected $_browserObj=null;
	
	public function setDebug($bool)
	{
		$this->_debug	= $bool;
	}
	public function getDebug()
	{
		return $this->_debug;
	}
}