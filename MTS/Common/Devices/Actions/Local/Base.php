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
}