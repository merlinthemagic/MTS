<?php
//© 2016 Martin Madsen
namespace MTS\Common\Data\Computer\OperatingSystems\Linux;

class Base
{
	protected $_classStore=array();
	
	public function setActionClass($classObj)
	{
		//this Action class will be responsible for retriving additional data about the operating system
		$this->_classStore['actionObj']	= $classObj;
	}
	
	public function getType()
	{
		return 'Linux';
	}
}