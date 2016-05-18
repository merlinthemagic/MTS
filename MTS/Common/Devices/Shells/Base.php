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
		//to not use a delimitor set to false, this will force a read until the $idleTimeout or $maxTimeout is exceeded
		
		//$idleTimeout: max gap we wait between data returns. i.e. after command has been executed we get some data back, 
		//then the program processes and 2000ms later we get the rest of the data. in that case set to something safe like 3000
		//default is determined by the shell class
		
		//$maxTimeout: the absolute longest the command is allowed to run
		//set to 0 if you do not wish the receive a return from the command
		//use if a command continues to return data, i.e ping, without a max the command would never return
		//because the idle would not be exceeded
		//default is determined by the shell class

		try {
			return $this->shellStrExecute($strCmd, $delimitor, $idleTimeout, $maxTimeout);
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function killLastProcess()
	{
		$this->shellKillLastProcess();
	}
}