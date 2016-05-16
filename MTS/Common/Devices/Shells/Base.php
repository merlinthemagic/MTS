<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Shells;

class Base
{
	public function exeCmd($strCmd, $delimitor=null, $idleTimeout=null, $maxTimeout=null)
	{
		//$strCmd: string command to execute
		
		//$delimitor: regex when matched ends the command and returns data.
		//Only use if the command does not end in a regular prompt, or you want only a partial return
		//to not use a delimitor set to false, this will force a read until the $idleTimeout is met
		
		//$idleTimeout: max time we wait for data to be returned. 
		//only use if i.e. no return is required or a command takes a long time between returning data
		
		//$maxTimeout: the absolute longest the command is allowed to run
		//use if a command continues to return data, i.e ping, without a max the command would never return
		//because the idle would not be exceeded

		try {
			return $this->shellStrExecute($strCmd, $delimitor, $idleTimeout, $maxTimeout);
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
}