<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices;

class Device
{
	protected $_debug=false;
	protected $_classStore=array();
	protected $_shellObjs=array();
	protected $_browserObjs=array();
	
	public function setDebug($bool)
	{
		$this->_debug	= $bool;
	}
	public function getDebug()
	{
		return $this->_debug;
	}
}