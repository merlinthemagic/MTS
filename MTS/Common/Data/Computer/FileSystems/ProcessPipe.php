<?php
//© 2016 Martin Madsen
namespace MTS\Common\Data\Computer\FileSystems;

class ProcessPipe
{
	private $_inFile=null;
	private $_outFile=null;
	private $_errFile=null;
	
	private $_stdinPos=0;
	private $_stdoutPos=0;
	private $_stderrPos=0;

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
	public function setInputFile($fileObj)
	{
		\MTS\Factories::getFiles()->getFilesTool()->isFileObj($fileObj, true);
		$this->_inFile	= $fileObj;
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
			\MTS\Factories::getFiles()->getFilesTool()->isFile($this->getInputFile(), true);
			$this->getInputFile()->setContent($data);
			\MTS\Factories::getFiles()->getFilesTool()->appendContent($this->getInputFile());
			$this->getInputFile()->setContent(null);
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
			if ($this->_stdoutPos < $sizeBytes) {
				//new content avaliable
				\MTS\Factories::getFiles()->getFilesTool()->getContent($this->getOutputFile(), $this->_stdoutPos, $sizeBytes);
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
			if ($this->_stderrPos < $sizeBytes) {
				//new content avaliable
				\MTS\Factories::getFiles()->getFilesTool()->getContent($this->getErrorFile(), $this->_stderrPos, $sizeBytes);
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