<?php
//© 2016 Martin Madsen
namespace MTS\Common\Data\Computer\FileSystems;

class ProcessPipe
{
	private $_parentProc=null;
	private $_inType=null;
	private $_inFile=null;
	private $_outFile=null;
	private $_errFile=null;
	
	private $_stdinPos=0;
	private $_stdoutPos=0;
	private $_stderrPos=0;
	
	public function __destruct()
	{
		if ($this->_inType == "resource") {
			@fclose($this->_inFile);
			@proc_close($this->_parentProc);
		}
	}

	public function getInputFile()
	{
		return $this->_inFile;
	}
	public function getOutputFile()
	{
		return $this->_outFile;
	}
	public function getErrorFile()
	{
		return $this->_errFile;
	}
	public function getInputPosition()
	{
		return $this->_stdinPos;
	}
	public function getOutputPosition()
	{
		return $this->_stdoutPos;
	}
	public function getErrorPosition()
	{
		return $this->_stderrPos;
	}
	public function setInputFile($pipeObj, $parentProc=null)
	{
		$isFile	= \MTS\Factories::getFiles()->getFilesTool()->isFileObj($pipeObj, false);
		if ($isFile === true) {
			$this->_inType	= 'file';
			$this->_inFile	= $pipeObj;
		} elseif (is_resource($pipeObj) === true) {
			$this->_inType		= 'resource';
			$this->_inFile		= $pipeObj;
			$this->_parentProc	= $parentProc;
		} else {
			throw new \Exception(__METHOD__ . ">> Input pipe is invalid");
		}
	}
	public function setOutputFile($fileObj)
	{
		\MTS\Factories::getFiles()->getFilesTool()->isFileObj($fileObj, true);
		$this->_outFile	= $fileObj;
	}
	public function setErrorFile($fileObj)
	{
		\MTS\Factories::getFiles()->getFilesTool()->isFileObj($fileObj, true);
		$this->_errFile	= $fileObj;
	}
	public function getOutputFileSize()
	{
		try {
			//clear cache before getting size, php tends to cache file attrbutes
			clearstatcache(true, $this->getOutputFile()->getPathAsString());
			return \MTS\Factories::getFiles()->getFilesTool()->getSize($this->getOutputFile());
		
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function getErrorFileSize()
	{
		try {
			//clear cache before getting size, php tends to cache file attrbutes
			clearstatcache(true, $this->getErrorFile()->getPathAsString());
			return \MTS\Factories::getFiles()->getFilesTool()->getSize($this->getErrorFile());
		
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function resetReadPosition()
	{
		//force the read position to the size of the file
		$this->_stdoutPos	= $this->getOutputFileSize();
	}

	public function strWrite($data)
	{
		try {
			if ($this->_inType == "file") {
				\MTS\Factories::getFiles()->getFilesTool()->isFile($this->getInputFile(), true);
				$this->getInputFile()->setContent($data);
				\MTS\Factories::getFiles()->getFilesTool()->appendContent($this->getInputFile());
				$this->getInputFile()->setContent(null);
			} elseif ($this->_inType == "resource") {
				fwrite($this->getInputFile(), $data);
			}
			
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function strRead()
	{
		try {
			$content	= "";
			$sizeBytes	= $this->getOutputFileSize();
			if ($this->getOutputPosition() < $sizeBytes) {
				//new content avaliable
				\MTS\Factories::getFiles()->getFilesTool()->getContent($this->getOutputFile(), $this->getOutputPosition(), $sizeBytes);
				$this->_stdoutPos	= $sizeBytes;
				
				$content	= $this->getOutputFile()->getContent();
				$this->getOutputFile()->setContent(null);
			}
	
			return $content;
		
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
	public function strErrorRead()
	{
		try {
			$content	= "";
			$sizeBytes	= $this->getErrorFileSize();
			if ($this->getErrorPosition() < $sizeBytes) {
				//new content avaliable
				\MTS\Factories::getFiles()->getFilesTool()->getContent($this->getErrorFile(), $this->getErrorPosition(), $sizeBytes);
				$this->_stderrPos	= $sizeBytes;
					
				$content	= $this->getErrorFile()->getContent();
				$this->getErrorFile()->setContent(null);
			}
			
			return $content;
		} catch (\Exception $e) {
			switch($e->getCode()){
				default;
				throw $e;
			}
		}
	}
}