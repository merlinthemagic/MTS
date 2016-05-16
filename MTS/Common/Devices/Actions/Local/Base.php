<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Local;

class Base
{
	protected function shellExec($cmdString)
	{
		exec($cmdString, $rData);
		$cReturn	= implode("\n", $rData);
		return $cReturn;
	}
}