<?php
//© 2016 Martin Madsen
namespace MTS\Factories;

class Devices
{
	private $_classStore=array();

	public function getLocalHost()
	{
		//dont cache, each call should return new instance
		return new \MTS\Common\Devices\Types\Localhost();
	}
}