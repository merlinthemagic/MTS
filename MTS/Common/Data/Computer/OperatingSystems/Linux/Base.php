<?php
//© 2016 Martin Madsen
namespace MTS\Common\Data\Computer\OperatingSystems\Linux;

class Base
{
	protected $_classStore=array();
	
	public function getType()
	{
		return 'Linux';
	}
}