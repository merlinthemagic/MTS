<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Local\Host;
use MTS\Common\Devices\Actions\Local\Base;

class Processes extends Base
{
	public function isRunningPid($pid)
	{
		if (preg_match("/^[0-9]+$/", $pid)) {
			$this->_classStore['requestType']	= __FUNCTION__;
			$this->_classStore['pid']			= $pid;
			return $this->execute();
		} else {
			throw new \Exception(__METHOD__ . ">> Invalid Input ");
		}
	}
	public function sigTermPid($pid, $delay=null, $timeOut=5)
	{
		if (preg_match("/^[0-9]+$/", $pid)) {
			$this->_classStore['requestType']	= __FUNCTION__;
			$this->_classStore['pid']			= $pid;
			$this->_classStore['delay']			= $delay;
			$this->_classStore['timeout']		= $timeOut;
			return $this->execute();
		} else {
			throw new \Exception(__METHOD__ . ">> Invalid Input ");
		}
	}
	public function createSleepProcess($lifeTime)
	{
		if (preg_match("/^[0-9]+$/", $lifeTime)) {
			$this->_classStore['requestType']	= __FUNCTION__;
			$this->_classStore['lifeTime']		= $lifeTime;
			return $this->execute();
		} else {
			throw new \Exception(__METHOD__ . ">> Invalid Input ");
		}
	}
	private function execute()
	{
		$requestType	= $this->_classStore['requestType'];
		$osObj			= \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
		
		if ($requestType == 'isRunningPid') {
			$pid		= $this->_classStore['pid'];
			
			if ($osObj->getType() == "Linux") {
				$killExe	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile("kill");
				if ($killExe !== false) {
					$cmdString	= "(".$killExe->getPathAsString()." -0 ".$pid." 2> /dev/null && echo \"Alive\" ) || echo \"Dead\"";
					$rData		= $this->shellExec($cmdString);
					
					if ($rData == "Alive") {
						return true;
					} elseif ($rData == "Dead") {
						return false;
					}
				}  else {
					throw new \Exception(__METHOD__ . ">> Cannot Determine if PID: " . $pid . " is running, missing application 'kill'");
				}
			} elseif ($osObj->getType() == "Windows") {

				$cmdString	= "tasklist";
				$rData		= $this->shellExec($cmdString);
				$lines		= explode("\n", $rData);
				
				if (count($lines) > 1) {
					foreach ($lines as $line) {
						if (preg_match("/(.+)\s+([0-9]+)\s+([0-9]+)\s+(.*)/", $line, $lineParts) == 1) {
							if ($lineParts[2] == $pid) {
								//found the pid alive
								return true;
							}
						}
					}
					
					return false;
					
				} else {
					//command did not execute, at the very least we should have the 2 header lines
				}
			}

		} elseif ($requestType == 'createSleepProcess') {
			//create a local sleep process
			$lifeTime		= $this->_classStore['lifeTime'];
			
			if ($osObj->getType() == "Linux") {
					
				$psExe		= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile("ps");
					
				if ($psExe !== false) {
			
					$sleepCmd	= "sleep ".$lifeTime."s";
					$cmdStr1	= "( ".$sleepCmd." > /dev/null & ) > /dev/null 2>&1";
					exec($cmdStr1, $rData1);
						
					$cmdStr2	= $psExe->getPathAsString() . " ax | grep \"" . $sleepCmd . "\" | grep -v \"grep\"";
					exec($cmdStr2, $rData2);
					
					if (is_array($rData2) === true) {
						//if multiple sleeps exist for the same period, get the last one
						$rData2		= array_filter($rData2);
						$lLine		= trim(array_pop($rData2));
						if (preg_match("/^([0-9]+)/", $lLine, $rawPid) == 1) {
							return intval($rawPid[1]);
						}
					}

				} else {
					throw new \Exception(__METHOD__ . ">> Cannot Create Sleep, missing application 'ps'");
				}
			}

		} elseif ($requestType == 'sigTermPid') {
			$pid		= $this->_classStore['pid'];
			$delay		= $this->_classStore['delay'];
			$timeout	= $this->_classStore['timeout'];
			$running	= $this->isRunningPid($pid);

			if ($running === true) {
				
				if ($osObj->getType() == "Linux") {
					
					//presense of kill validated by isRunningPID()
					$killExe	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile("kill");
					if ($delay === null) {
						
						$cmdString	= $killExe->getPathAsString() . " -SIGTERM " . $pid;
						$this->shellExec($cmdString);
						
						//process must have terminated by this time
						$termTime	= time() + $timeout;
						
						//validate the process is dead
						while (true) {
							$running	= $this->isRunningPid($pid);
							if ($running === true) {
								if (time() >= $termTime) {
									throw new \Exception(__METHOD__ . ">> Failed to SIGTERM PID: " . $pid . ", still running");
								}
							} else {
								//process is dead
								return;
							}
						}
						
					} else {
						//the kill should be executed at a delay
						$cmdString	= "( sleep ".$delay."s && (".$killExe->getPathAsString()." -0 ".$pid." 2> /dev/null && ".$killExe->getPathAsString()." -SIGTERM ".$pid." & ) & ) > /dev/null 2>&1";
						$this->shellExec($cmdString);
						return;
					}
				} elseif ($osObj->getType() == "Windows") {

					if ($delay === null) {
						
						$cmdString	= "Taskkill /PID ".$pid." /F ";
						$this->shellExec($cmdString);
						
						//process must have terminated by this time
						$termTime	= time() + $timeout;
						
						//validate the process is dead
						while (true) {
							$running	= $this->isRunningPid($pid);
							if ($running === true) {
								if (time() >= $termTime) {
									throw new \Exception(__METHOD__ . ">> Failed to SIGTERM PID: " . $pid . ", still running");
								}
							} else {
								//process is dead
								return;
							}
						}
						
					} else {
						
						$cmdString	= "START \"seq\" cmd /c \"ping -n " .$delay. " 127.0.0.1 && Taskkill /PID ".$pid." /F\"";
						//cannot get exec() to return without waiting for process to exit
						//should get fixed since we dont want to depend on another function for MTS to run
						pclose(popen($cmdString, "r"));
						return;
					}
				}

			} else {
				//pid not running, nothing to do
				return;
			}
		}
		
		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}