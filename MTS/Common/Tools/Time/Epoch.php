<?php
//© 2016 Martin Madsen
namespace MTS\Common\Tools\Time;

class Epoch
{
	public function getCurrentMicroTime()
	{
		//time of day micro secs varies in length
		$stime		= gettimeofday();
		return ($stime["sec"] . "." . str_repeat(0, (6 - strlen($stime["usec"]))) . $stime["usec"]);
	}
	public function getCurrentMiliTime()
	{
		return substr($this->getCurrentMicroTime(), 0, -3);
	}
}