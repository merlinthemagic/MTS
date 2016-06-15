<?php
//© 2016 Martin Madsen
namespace MTS\Factories;

class Devices
{
	protected $_classStore=array();

	public function getLocalHost()
	{
		//dont cache, each call should return new instance
		return new \MTS\Common\Devices\Types\Localhost();
	}
	public function getRemoteHost($hostname=null)
	{
		//dont cache, each call should return new instance
		$rHost	= new \MTS\Common\Devices\Types\Remotehost();
		if ($hostname !== null) {
			$rHost->setHostname($hostname);
		}
		return $rHost; 
	}
}