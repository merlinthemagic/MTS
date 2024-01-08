<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Browsers;

class PhantomJS extends Base implements BrowserInterface
{
	private $_procPipe=null;
	private $_cmdMaxTimeout=null;
	private $_partialReturn="";
	private $_debugFile=null;
	
	public function setPipes($procPipeObj)
	{
		$this->_procPipe		= $procPipeObj;
	}
	public function getPipes()
	{
		return $this->_procPipe;
	}
	private function getDebugFile()
	{
		return $this->_debugFile;
	}
	public function getDebugFileContent()
	{
		$content	= "";
		if ($this->getDebugFile() !== null) {
			$exist	= \MTS\Factories::getFiles()->getFilesTool()->isFile($this->getDebugFile());
			if ($exist === true) {
				\MTS\Factories::getFiles()->getFilesTool()->getContent($this->getDebugFile());
				$content	.= $this->getDebugFile()->getContent();
			}
		}
		return $content;
	}
	public function setURL($windowObj, $url)
	{
		try {
				
			//by setting the url to a new value all children must be terminated
			$children	= $windowObj->getChildren();
			foreach ($children as $child) {
				$this->closeWindow($child);
			}
				
			$options			= array();
			$options['url']		= $url;

			$result				= $this->getResultArray($this->browserExecute($windowObj, 'seturl', $options));
			if ($result['code'] != 200) {
				throw new \Exception(__METHOD__ . ">> Got result code: " . $result['code'] . ", EMsg: " . $result['error']['msg'] . ", ECode: " . $result['error']['code']);
			}
	
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function getURL($windowObj)
	{
		try {

			$result				= $this->getResultArray($this->browserExecute($windowObj, 'geturl'));
			if ($result['code'] != 200) {
				throw new \Exception(__METHOD__ . ">> Got result code: " . $result['code'] . ", EMsg: " . $result['error']['msg'] . ", ECode: " . $result['error']['code']);
			} else {
				return $result['data']['script'];
			}
	
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function screenshot($windowObj, $format)
	{
		try {

			//validate
			$format						= strtolower($format);
			$options					= array();
			$options['imgFormat']		= strtolower($format);
			
			if (preg_match("/(png|jpeg)/", $format) == 0) {
				throw new \Exception(__METHOD__ . ">> Invalid image format: " . $format . ". Allowed: png|jpeg");
			}

			$result						= $this->getResultArray($this->browserExecute($windowObj, 'screenshot', $options));
			if ($result['code'] != 200) {
				throw new \Exception(__METHOD__ . ">> Got result code: " . $result['code'] . ", EMsg: " . $result['error']['msg'] . ", ECode: " . $result['error']['code']);
			} else {
				//decode, we want to relay raw information
				return base64_decode($result['data']['image']);
			}
	
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	protected function browserCloseWindow($windowObj)
	{
		$result						= $this->getResultArray($this->browserExecute($windowObj, 'closewindow'));
		if ($result['code'] != 200) {
			throw new \Exception(__METHOD__ . ">> Got result code: " . $result['code'] . ", EMsg: " . $result['error']['msg'] . ", ECode: " . $result['error']['code']);
		}
	}
	public function getDom($windowObj)
	{
		try {
				
			$result		= $this->getResultArray($this->browserExecute($windowObj, 'getdom'));
			if ($result['code'] != 200) {
				throw new \Exception(__METHOD__ . ">> Got result code: " . $result['code'] . ", EMsg: " . $result['error']['msg'] . ", ECode: " . $result['error']['code']);
			} else {
				//decode, we want to relay raw information
				return urldecode($result['data']['dom']);
			}
	
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function mouseEventOnElement($windowObj, $selector, $event)
	{
		try {
	
			$event					= strtolower($event);
			$options				= array();
			$options['selector']	= $selector;
			$options['mouseEvent']	= $event;
			
			if (preg_match("/(up|down|move|rightdoubleclick|rightclick|leftdoubleclick|leftclick)/", $event) == 0) {
				throw new \Exception(__METHOD__ . ">> Invalid mouse event: " . $event . ". Allowed: up|down|move|rightdoubleclick|rightclick|leftdoubleclick|leftclick");
			}

			$result					= $this->getResultArray($this->browserExecute($windowObj, 'mouseeventonelement', $options));
			if ($result['code'] != 200) {
				throw new \Exception(__METHOD__ . ">> Got result code: " . $result['code'] . ", EMsg: " . $result['error']['msg'] . ", ECode: " . $result['error']['code']);
			}
	
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function focusElement($windowObj, $selector)
	{
		try {
				
			$options				= array();
			$options['selector']	= $selector;
	
			$result					= $this->getResultArray($this->browserExecute($windowObj, 'focuselement', $options));
			if ($result['code'] != 200) {
				throw new \Exception(__METHOD__ . ">> Got result code: " . $result['code'] . ", EMsg: " . $result['error']['msg'] . ", ECode: " . $result['error']['code']);
			}
	
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function getElement($windowObj, $selector)
	{
		try {
	
			$options				= array();
			$options['selector']	= $selector;
	
			$result					= $this->getResultArray($this->browserExecute($windowObj, 'getelement', $options));
			if ($result['code'] != 200) {
				throw new \Exception(__METHOD__ . ">> Got result code: " . $result['code'] . ", EMsg: " . $result['error']['msg'] . ", ECode: " . $result['error']['code']);
			} else {
				$rData				= json_decode($result['data']['dom'], true);
				$rData["innerHTML"]	= urldecode($rData["innerHTML"]);
				return $rData;
			}
	
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function getCookies($windowObj)
	{
		try {

			$result					= $this->getResultArray($this->browserExecute($windowObj, 'getcookies'));
			if ($result['code'] != 200) {
				throw new \Exception(__METHOD__ . ">> Got result code: " . $result['code'] . ", EMsg: " . $result['error']['msg'] . ", ECode: " . $result['error']['code']);
			} else {
				return json_decode($result['data']['dom'], true);
			}
	
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function getSelectorExists($windowObj, $selector)
	{
		try {
	
			$options				= array();
			$options['selector']	= $selector;
	
			$result					= $this->getResultArray($this->browserExecute($windowObj, 'getselectorexists', $options));
			if ($result['code'] != 200) {
				throw new \Exception(__METHOD__ . ">> Got result code: " . $result['code'] . ", EMsg: " . $result['error']['msg'] . ", ECode: " . $result['error']['code']);
			} else {
				if ($result['data']['dom'] == 1) {
					return true;
				} else {
					return false;
				}
			}
	
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function getDocument($windowObj)
	{
		try {
	
			$result					= $this->getResultArray($this->browserExecute($windowObj, 'getdocument'));
			if ($result['code'] != 200) {
				throw new \Exception(__METHOD__ . ">> Got result code: " . $result['code'] . ", EMsg: " . $result['error']['msg'] . ", ECode: " . $result['error']['code']);
			} else {
				return json_decode($result['data']['dom'], true);
			}
	
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	
	public function setCookie($windowObj, $name, $value, $domain, $path, $expireTime, $serverOnly, $secureOnly)
	{
		try {

			//validate
			if ($name == "") {
				throw new \Exception(__METHOD__ . ">> Name is required");
			} elseif ($value == "") {
				throw new \Exception(__METHOD__ . ">> Value is required");
			} elseif (is_bool($serverOnly) === false) {
				throw new \Exception(__METHOD__ . ">> Server only must be bool");
			} elseif (is_bool($secureOnly) === false) {
				throw new \Exception(__METHOD__ . ">> Secure must be bool");
			}
			
			if ($path == "") {
				$path	= "/";
			}
			if ($expireTime!= "") {
				
				if (ctype_digit((string) $expireTime) === false || $expireTime < 0 || $expireTime > 2147483647) {
					throw new \Exception(__METHOD__ . ">> Expiration must be integer between 0 and 2147483647");
				}
				
			} else {
				$expireTime = 2147483647;
			}

			$curUrl			= $windowObj->getURL();
			if ($curUrl == "") {
				throw new \Exception(__METHOD__ . ">> Domain is required, current window does not have a URL");
			}
			
			$urlParts	= parse_url($curUrl);
			$urlhost	= trim($urlParts["host"]);
			
			if ($domain == "") {
				$domain		= $urlhost;
			} else {

				if (strpos($urlhost, $domain) === false) {
					throw new \Exception(__METHOD__ . ">> Cookie domain must match the current url domain");
				}
			}

			$options				= array();
			$options['name']		= $name;
			$options['value']		= $value;
			$options['domain']		= $domain;
			$options['path']		= $path;
			$options['expiration']	= $expireTime;
			$options['httponly']	= $serverOnly;
			$options['secure']		= $secureOnly;

			$result					= $this->getResultArray($this->browserExecute($windowObj, 'setcookie', $options));
			if ($result['code'] != 200) {
				throw new \Exception(__METHOD__ . ">> Got result code: " . $result['code'] . ", EMsg: " . $result['error']['msg'] . ", ECode: " . $result['error']['code']);
			}
			
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function sendKeyPresses($windowObj, $keys, $modifiers)
	{
		try {
			
			if (is_array($keys) === false) {
				$keys	= str_split($keys);
			}

			$options				= array();
			$options['keys']		= $keys;
			$options['modifiers']	= array();
			
			foreach ($modifiers as $modifier) {
				$modKey	= strtolower($modifier);
				if (preg_match("/(alt|shift|ctrl|meta|keypad)/", $modKey) == 0) {
					throw new \Exception(__METHOD__ . ">> Invalid modifier Key: " . $modKey . ". Allowed: alt|shift|ctrl|meta|keypad");
				} else {
					$options['modifiers'][]	= $modKey;
				}
			}
	
			$result					= $this->getResultArray($this->browserExecute($windowObj, 'sendkeypresses', $options));
			if ($result['code'] != 200) {
				throw new \Exception(__METHOD__ . ">> Got result code: " . $result['code'] . ", EMsg: " . $result['error']['msg'] . ", ECode: " . $result['error']['code']);
			}
	
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function clickElement($windowObj, $selector)
	{
		try {
		
			$options				= array();
			$options['selector']	= $selector;
		
			$result					= $this->getResultArray($this->browserExecute($windowObj, 'clickelement', $options));
			if ($result['code'] != 200) {
				throw new \Exception(__METHOD__ . ">> Got result code: " . $result['code'] . ", EMsg: " . $result['error']['msg'] . ", ECode: " . $result['error']['code']);
			}
		
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function loadJS($windowObj, $scriptData)
	{
		try {
			$scriptName		= "JS_" . uniqid();
			$scriptFile		= \MTS\Factories::getFiles()->getFile($scriptName, $this->getPipes()->getOutputFile()->getDirectory()->getPathAsString());
			$scriptFile->setContent($scriptData);
			\MTS\Factories::getFiles()->getFilesTool()->setContent($scriptFile);
				
			$options					= array();
			$options['scriptPath']		= $scriptFile->getPathAsString();
	
			$result						= $this->getResultArray($this->browserExecute($windowObj, 'loadjs', $options));
			if ($result['code'] != 200) {
				throw new \Exception(__METHOD__ . ">> Got result code: " . $result['code'] . ", EMsg: " . $result['error']['msg'] . ", ECode: " . $result['error']['code']);
			}
	
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function callJSFunction($windowObj, $functionName)
	{
		try {
			
			$options					= array();
			$options['functionName']	= $functionName;
	
			$result						= $this->getResultArray($this->browserExecute($windowObj, 'jscallfunction', $options));
			if ($result['code'] != 200) {
				throw new \Exception(__METHOD__ . ">> Got result code: " . $result['code'] . ", EMsg: " . $result['error']['msg'] . ", ECode: " . $result['error']['code']);
			} else {
				return $result['data']['script'];
			}
	
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	protected function browserSetDebug()
	{
		try {
			$options					= array();
			$options['debug']			= 0;
			if ($this->getDebug() === true) {
				if ($this->getDebugFile() === null) {
					$this->_debugFile		= \MTS\Factories::getFiles()->getFile("debug", $this->getPipes()->getOutputFile()->getDirectory()->getPathAsString());
					\MTS\Factories::getFiles()->getFilesTool()->create($this->_debugFile);
				}
				
				$options['debug']		= 1;
				$options['debugPath']	= $this->getDebugFile()->getPathAsString();
			}
		
			$result						= $this->getResultArray($this->browserExecute(null, 'setdebug', $options));
			if ($result['code'] != 200) {
				throw new \Exception(__METHOD__ . ">> Got result code: " . $result['code'] . ", EMsg: " . $result['error']['msg'] . ", ECode: " . $result['error']['code']);
			} else {
				//if we had a debug file delete it
				if ($this->getDebug() === false && $this->getDebugFile() !== null) {
					\MTS\Factories::getFiles()->getFilesTool()->delete($this->getDebugFile());
					$this->_debugFile	= null;
				}
			}
		
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	protected function browserInitialize()
	{
		if ($this->getInitialized() === null) {
	
			try {
				$this->_initialized = 'setup';
					
				$options					= array();
				$options['stdInPath']		= $this->getPipes()->getInputFile()->getPathAsString();
				$options['stdOutPath']		= $this->getPipes()->getOutputFile()->getPathAsString();
				$options['stdErrPath']		= $this->getPipes()->getErrorFile()->getPathAsString();
				
				//+ 5 so we can get return from successful termination if shutdown by execution time exceeded.
				$exeTimeout		= \MTS\Factories::getActions()->getLocalPhpEnvironment()->getRemainingExecutionTime();
				$options['terminationSecs']	= floor($exeTimeout + 5);

				//use the result
				$result				= $this->getResultArray($this->browserExecute(null, 'initialize', $options));
				if ($result['code'] != 200) {
					throw new \Exception(__METHOD__ . ">> Got code: " . $result['code']);
				}
	
				$this->_procPID	= $result['PID'];
				$this->_initialized = true;
	
			} catch (\Exception $e) {
					
				switch($e->getCode()){
					default;
					$this->_initialized = false;
					throw $e;
				}
			}
		}
	}
	
	protected function browserTerminate()
	{
		try {
			
			//in case the browser class was instanciated, but phantomJS was never initiated, 
			//we will need to initiate before terminating, because without any data in stdIn the process is blocked
			//and we get a zombie process
			$this->browserInitialize();

		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				if ($this->getDebug() === true) {
					$this->addDebugData("While Terminating, Initialize threw the following error: " . $e->getMessage());
				}
				$this->_initialized = "terminating";
				throw $e;
			}
		}
		
		//now we are ready to terminate
		$this->_initialized = "terminating";

		$result		= $this->getResultArray($this->browserExecute(null, 'terminate', null));
		if ($result['code'] != 200) {
			if ($this->getDebug() === true) {
				$this->addDebugData("Termination command received the following return code: " . $result['code']);
			}
			throw new \Exception(__METHOD__ . ">> Got code: " . $result['code']);
		}

		$this->_initialized	= false;
		$this->addDebugData("Browser terminated successfully.");
	}
	
	//read and write
	protected function browserExecute($windowObj=null, $cmd=null, $options=null, $maxTimeout=null)
	{
		try {
			
			if ($this->getInitialized() === false) {
				throw new \Exception(__METHOD__ . ">> Cannot execute commands. Browser has been terminated");
			} elseif ($this->getInitialized() === null) {
				$this->browserInitialize();
			}
	
			if ($maxTimeout === null) {
				$maxTimeout		= $this->getDefaultExecutionTime();
			}
			
			$cmdArr						= array();
			$cmdArr['cmd']				= array();
			$cmdArr['cmd']['id']		= uniqid();
			$cmdArr['cmd']['name']		= strtolower($cmd);
			$cmdArr['cmd']['timeout']	= $maxTimeout;
			
			if (is_array($options) === true) {
				$cmdArr['cmd']['options']	= $options;
			} else {
				$cmdArr['cmd']['options']	= array();
			}
	
			if (is_object($windowObj) === true) {
				
				$size		= $windowObj->getSize();
				$raster		= $windowObj->getRasterSize();
				
				$cmdArr['cmd']['window']						= array();
				$cmdArr['cmd']['window']['UUID']				= $windowObj->getUUID();
				$cmdArr['cmd']['window']['loadImages']			= 1;
				if ($windowObj->getLoadImages() === false) {
					$cmdArr['cmd']['window']['loadImages']		= 0;
				}
				$cmdArr['cmd']['window']['userAgent']			= "";
				if ($windowObj->getUserAgent() !== null) {
					$cmdArr['cmd']['window']['userAgent']		= $windowObj->getUserAgent();
				}
				
				$cmdArr['cmd']['window']['width']				= $size['width'];
				$cmdArr['cmd']['window']['height']				= $size['height'];

				$cmdArr['cmd']['window']['raster']['top']		= $raster['top'];
				$cmdArr['cmd']['window']['raster']['left']		= $raster['left'];
				$cmdArr['cmd']['window']['raster']['width']		= $raster['width'];
				$cmdArr['cmd']['window']['raster']['height']	= $raster['height'];
				
				$scroll	= $windowObj->getScrollPosition();
				$cmdArr['cmd']['window']['scroll']['top']		= $scroll['top'];
				$cmdArr['cmd']['window']['scroll']['left']		= $scroll['left'];

			} else {
				$cmdArr['cmd']['window']			= array();
			}
			
			//turn array into jSON
			$cmdJson	= json_encode($cmdArr, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);
		
			//clean up
			$this->getPipes()->resetReadPosition();
			
			//execute command
			$wData		= $this->browserWrite($cmdJson);
			if (strlen($wData['error'] ?? '') > 0) {
				throw new \Exception(__METHOD__ . ">> Failed to write command to browser. Error: " . $wData['error']);
			} else {
				
				if ($maxTimeout > 0) {
					
					$rData	= $this->browserRead($cmdJson);
					if (strlen($rData['error'] ?? '') > 0) {
						throw new \Exception(__METHOD__ . ">> Failed to read command from browser. Error: " . $rData['error']);
					} else {
						
						//create / remove children as needed
						if (is_object($windowObj) === true) {
							$deCmd		= json_decode($rData['data'], true);
							
							//parent logic
							$parentUUID	= $deCmd['result']['parent'];
							if ($parentUUID !== null) {
								$parentObj	= $this->getWindow($parentUUID);
								if ($parentObj !== false) {
									$curParent	= $windowObj->getParent();
									if ($curParent !== null) {
										if ($curParent->getUUID() != $parentUUID) {
											throw new \Exception(__METHOD__ . ">> Window: " . $windowObj->getUUID . ", is reported to have parent: " . $parentUUID . ", however the current parent is ".$curParent->getUUID().". Should not be possible.");
										} else {
											//parent already set, no change
										}
									} else {
										//no current parent, add it
										$windowObj->setParent($parentObj);
									}
								} else {
									throw new \Exception(__METHOD__ . ">> Window: " . $windowObj->getUUID() . ", is reported to have parent: " . $parentUUID . ", however the parent is unknown. Should not be possible.");
								}
							}
							
							//parent logic
							$parentUUID	= $deCmd['result']['parent'];
							if ($parentUUID !== null) {
								$parentObj	= $this->getWindow($parentUUID);
								if ($parentObj !== false) {
									$curParent	= $windowObj->getParent();
									if ($curParent !== null) {
										if ($curParent->getUUID() != $parentUUID) {
											throw new \Exception(__METHOD__ . ">> Window: " . $windowObj->getUUID . ", is reported to have parent: " . $parentUUID . ", however the current parent is ".$curParent->getUUID().". Should not be possible.");
										} else {
											//parent already set, no change
										}
									} else {
										//no current parent, add it
										$windowObj->setParent($parentObj);
									}
								} else {
									throw new \Exception(__METHOD__ . ">> Window: " . $windowObj->getUUID . ", is reported to have parent: " . $parentUUID . ", however the parent is unknown. Should not be possible.");
								}
							}
							
							
							//child logic
							$pjsChildren	= $deCmd['result']['children'];
							$childObjs		= $windowObj->getChildren();
							
							//start by removing children that are no longer present
							foreach ($childObjs as $childObj) {
								$exist	= false;
								foreach ($pjsChildren as $pjsChild) {
									if ($pjsChild['window']['uuid'] == $childObj->getUUID()) {
										$exist	= true;
										break;
									}
								}
								
								if ($exist === false) {
									//child no longer exist, remove
									$this->closeWindow($childObj);
								}
							}
							
							
							//add new children
							foreach ($pjsChildren as $pjsChild) {
								if ($windowObj->getChild($pjsChild['window']['uuid']) === false) {
									//child must be created
									$childObj	= $this->getNewWindow(null);
									//uuid comes from outside this time
									$childObj->setUUID($pjsChild['window']['uuid']);
									$childObj->setSize($pjsChild['window']['width'], $pjsChild['window']['height']);
									$childObj->setRasterSize($pjsChild['window']['raster']['top'], $pjsChild['window']['raster']['left'], $pjsChild['window']['raster']['width'], $pjsChild['window']['raster']['height']);
									
									$windowObj->setChild($childObj);
								}
							}
						}

						return $rData['data'];
					}
					
				} else {
					// no return requested
				}
			}
			
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	private function browserWrite($cmdJson)
	{
		$cmdStr	= "cmdStart>>>" . base64_encode($cmdJson) . "<<<cmdEnd\n\n";
		
		$return['error']	= null;
		$return['stime']	= \MTS\Factories::getTime()->getEpochTool()->getCurrentMiliTime();
		try {
			$this->getPipes()->strWrite($cmdStr);
		} catch (\Exception $e) {
	
			switch($e->getCode()){
				default;
				$return['error']	= $e->getMessage();
			}
		}
	
		$return['etime']	= \MTS\Factories::getTime()->getEpochTool()->getCurrentMiliTime();
	
		if ($this->_debug === true) {	
			$debugData				= $return;
			$debugData['type']		= __FUNCTION__;
			$debugData['cmdJson']	= $cmdJson;
			$this->addDebugData($debugData);
		}
		return $return;
	}
	private function browserRead($cmdJson)
	{
		$decodedOrigCmd		= json_decode($cmdJson, true);
		$cmdUUID			= $decodedOrigCmd['cmd']['id'];
		//getCurrentMiliTime returns a decimal
		//add 250 milliseconds that way phantomJS has time to return the error reason
		$maxWait			= ($decodedOrigCmd['cmd']['timeout'] + 250) / 1000;
		
		$return['error']	= null;
		//add partial data return if we read some of a return earlier
		$return['data']		= "";
		$lDataTime			= \MTS\Factories::getTime()->getEpochTool()->getCurrentMiliTime();
		$return['stime']	= $lDataTime;
		$done				= false;
	
		try {
			while ($done === false) {
				$newData	= $this->getPipes()->strRead();
				$exeTime	= \MTS\Factories::getTime()->getEpochTool()->getCurrentMiliTime();
	
				if ($newData != "") {
					$lDataTime			= $exeTime;
					$return['data']		.= $newData;
					
					$allData	= $this->_partialReturn . $return['data'];
					$startPos	= strpos($allData, "cmdStartReturn>>>");
					$endPos		= strpos($allData, "<<<cmdEndReturn");
					if ($startPos !== false && $endPos !== false) {
						//found end of a command return, lets see if we have the right one
						$cmdLines	= explode("cmdStartReturn>>>", $allData);
						
						foreach ($cmdLines as $cmdLine) {
							$cmdLine	= trim($cmdLine);
							if ($cmdLine != "") {
								$cmdEndPos		= strpos($cmdLine, "<<<cmdEndReturn");
								if ($cmdEndPos !== false) {
									$encodedCmd		= substr($cmdLine, 0, $cmdEndPos);
									$decodedCmd		= json_decode(base64_decode($encodedCmd), true);
									$rCmdUUID		= $decodedCmd['cmd']['id'];

									if ($rCmdUUID == $cmdUUID) {
										//we got the right right return
										//now figure out if there is spare data
										$origCmd	= "cmdStartReturn>>>" . $cmdLine;
										$origCmdLen	= strlen($origCmd);
										$allLen		= strlen($allData);
										
										if ($allLen == $origCmdLen) {
											//exact match
											$this->_partialReturn	= "";
										} else {
											//there is some data left
											$rCmdStart				= strpos($allData, $origCmd);
											//we trim because commands are followed by \n\n, if that is all that is left
											//we can just remove it
											$this->_partialReturn	= trim(substr($allData, ($rCmdStart + $origCmdLen)));
										}
										
										//override the return so only the command remains
										//also make pretty so the execution class get a return like what it sent
										$return['data']		= json_encode($decodedCmd, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);;
										$done	= true;
									}
								}
							}
						}
					}
					
				} else {
					//wait for a tiny bit no need to saturate the CPU
					usleep(10000);
				}
				
				if ($done === false && ($exeTime - $return['stime']) > $maxWait) {
					//timed out
					$return['error']	= 'timeout';
					$done				= true;
				}
			}
		} catch (\Exception $e) {
	
			switch($e->getCode()) {
				default;
				$return['error']	= $e->getMessage();
			}
		}
		
		if ($return['error'] !== null) {
			//we have an error, all data is partial
			$this->_partialReturn	= $this->_partialReturn . $return['data'];
		}
		
		$return['etime']	= \MTS\Factories::getTime()->getEpochTool()->getCurrentMiliTime();
	
		if ($this->_debug === true) {
			$debugData				= $return;
			$debugData['type']		= __FUNCTION__;
			$debugData['cmdJson']	= $cmdJson;
			$debugData['timeout']	= ($maxWait * 1000); //we want a milisec value
			$this->addDebugData($debugData);
		}

		return $return;
	}
	private function getResultArray($rCmdJson)
	{
		$deCmd	= json_decode($rCmdJson, true);
		return $deCmd['result'];
	}
}
