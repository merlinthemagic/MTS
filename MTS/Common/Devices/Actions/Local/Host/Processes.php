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
	public function sigTermPid($pid, $delay=null)
	{
		if (preg_match("/^[0-9]+$/", $pid)) {
			$this->_classStore['requestType']	= __FUNCTION__;
			$this->_classStore['pid']			= $pid;
			$this->_classStore['delay']			= $delay;
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
			$running	= $this->isRunningPid($pid);

			if ($running === true) {
				
				if ($osObj->getType() == "Linux") {
					
					//presense of kill validated by isRunningPID()
					$killExe	= \MTS\Factories::getActions()->getLocalApplicationPaths()->getExecutionFile("kill");
					if ($delay === null) {
						
						$cmdString	= $killExe->getPathAsString() . " -SIGTERM " . $pid;
						$this->shellExec($cmdString);
						usleep(100000);
						$running	= $this->isRunningPid($pid);
						if ($running === false) {
							return;
						} else {
							throw new \Exception(__METHOD__ . ">> Failed to SIGTERM PID: " . $pid . ", still running");
						}
						
					} else {
						//the kill should be executed at a delay
						$cmdString	= "( sleep ".$delay."s && (".$killExe->getPathAsString()." -0 ".$pid." 2> /dev/null && ".$killExe->getPathAsString()." -SIGTERM ".$pid." & ) & ) > /dev/null 2>&1";
						$this->shellExec($cmdString);
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