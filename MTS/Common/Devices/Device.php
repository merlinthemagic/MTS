<?php
namespace MTS\Common\Devices;

class Device
{
	protected function getAF()
	{
		//get the action factory
		return \MTS\Factories::getActions();
	}
}