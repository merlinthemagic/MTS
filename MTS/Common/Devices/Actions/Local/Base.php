<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Local;

class Base
{
	protected $_classStore=array();
	
	protected function shellExec($cmdString, $throw=false)
	{
		exec($cmdString, $rData, $status);
		if ($status == 0) {
			$cReturn	= implode("\n", $rData);
			return $cReturn;
		} else {
			if ($throw === true) {
				throw new \Exception(__METHOD__ . ">> Command Execution failed with status: '".$status."'. Command: '" .$cmdString. "'");
			} else {
				return null;
			}
		}
	}
}