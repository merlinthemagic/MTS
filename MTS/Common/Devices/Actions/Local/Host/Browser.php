<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Actions\Local\Host;
use MTS\Common\Devices\Actions\Local\Base;

class Browser extends Base
{
	public function getBrowser($browserName, $enableDebug=false)
	{
		$this->_classStore['requestType']	= __FUNCTION__;
		$this->_classStore['browserName']	= $browserName;
		$this->_classStore['enableDebug']	= $enableDebug;
		return $this->execute();
	}
	private function execute()
	{
		$requestType	= $this->_classStore['requestType'];
		$osObj			= \MTS\Factories::getActions()->getLocalOperatingSystem()->getOsObj();
		
		if ($requestType == 'getBrowser') {
			$browserName	= strtolower($this->_classStore['browserName']);
			$enableDebug	= $this->_classStore['enableDebug'];
			
			if ($osObj->getType() == "Linux") {
				if ($browserName == 'phantomjs') {
					
					$fileFact		= \MTS\Factories::getFiles();
					$pipeUuid		= uniqid();
					$workPath		= $fileFact->getDirectory(MTS_WORK_PATH . DIRECTORY_SEPARATOR . "LHB_" . $pipeUuid);
						
					if ($osObj->getArchitecture() == 64) {
						$pjsBin			= $fileFact->getVendorFile("pjslinux64");
					} elseif ($osObj->getArchitecture() == 32) {
						$pjsBin			= $fileFact->getVendorFile("pjslinux32");
					} else {
						throw new \Exception(__METHOD__ . ">> Phantomjs not available for OS Architecture: " . $osObj->getArchitecture());
					}
						
					$pjsCtrl		= $fileFact->getVendorFile("pjsctrl");
					
					$stdIn			= $fileFact->getFile("stdIn", $workPath->getPathAsString());
					$stdOut			= $fileFact->getFile("stdOut", $workPath->getPathAsString());
					$stdErr			= $fileFact->getFile("stdErr", $workPath->getPathAsString());
					
					$exeCmd			= "\"" . $pjsBin->getPathAsString() . "\" --local-storage-path=\"".$workPath->getPathAsString()."\" --web-security=false --local-to-remote-url-access=true --ignore-ssl-errors=true --load-images=true \"" . $pjsCtrl->getPathAsString() . "\"";
					
					//on RHEL 7 the xterm TERM will show a duplicate PS1 command that cannot be removed, also added a sleep 2s before deleting the std files, that way the files exist on the termination read / write
					$term			= 'vt100';
					$strCmd			= "mkfifo ".$stdIn->getPathAsString()."; ( sleep 1000d > ".$stdIn->getPathAsString()." & ( export TERM=".$term."; SLEEP_PID=$! ; " . $exeCmd." < ".$stdIn->getPathAsString()." > ".$stdOut->getPathAsString()." 2> ".$stdErr->getPathAsString()."; sleep 2s; rm -rf ".$stdIn->getPathAsString()."; rm -rf ".$stdOut->getPathAsString()."; rm -rf ".$stdErr->getPathAsString()."; rm -rf ".$workPath->getPathAsString()."; kill -s TERM \$SLEEP_PID & ) & ) > /dev/null 2>&1";
					
					//make the directory and out + err files
					$fileFact->getFilesTool()->create($stdOut);
					$fileFact->getFilesTool()->create($stdErr);
						
					//execute the command
					exec($strCmd);
					
					$errObj	= null;
					try {
					
						//sleep here so any error has time to be written to the stdErr file, the auto delete of error
						//will not happen until a few sec after the process is terminated
						usleep(10000);
						clearstatcache(true, $stdErr->getPathAsString());
						$fileFact->getFilesTool()->getContent($stdErr);
						if ($stdErr->getContent() != "") {
							throw new \Exception(__METHOD__ . ">> Failed to setup phantomJs on localHost Error: " . trim($stdErr->getContent()));
						}
					
						//if the server is busy it could take a bit to setup the shell
						$maxWait	= 30;
						$eTime		= time() + $maxWait;
						$stdInOk	= false;
						while ($eTime > time()) {
							$stdInOk	= $fileFact->getFilesTool()->isFile($stdIn);
							if ($stdInOk === true) {
								break;
							} else {
								usleep(50);
							}
						}
					
						if ($stdInOk !== true) {
							throw new \Exception(__METHOD__ . ">> Failed to setup phantomJs on localHost stdIn was never created");
						}
							
					} catch (\Exception $e) {
						switch($e->getCode()){
							default;
							$errObj = $e;
						}
					}
					
					if ($errObj === null) {
					
						//all good shell was created
						$stdPipe	= $fileFact->getProcessPipe($stdIn, $stdOut, $stdErr);
							
						$pjsBrowser	= new \MTS\Common\Devices\Browsers\PhantomJS();
						$pjsBrowser->setPipes($stdPipe);
						
						//this will init the browser if true
						$pjsBrowser->setDebug($enableDebug);
					
						return $pjsBrowser;
					} else {
							
						//clean up
						$fileFact->getFilesTool()->delete($stdIn);
						$fileFact->getFilesTool()->delete($stdOut);
						$fileFact->getFilesTool()->delete($stdErr);
						$fileFact->getDirectoriesTool()->delete($workPath);
							
						throw $errObj;
					}
						
				} else {
					throw new \Exception(__METHOD__ . ">> Not able to setup shell of type: " . $shellName);
				}
			}
		}
		
		throw new \Exception(__METHOD__ . ">> Not Handled for Request Type: " . $requestType);
	}
}