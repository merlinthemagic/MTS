<?php
//© 2016 Martin Madsen
namespace MTS\Factories;

class Files
{
	private $_classStore=array();
	
	public function getFile($strName=null, $strPath=null)
	{
		$fileObj	= new \MTS\Common\Data\Computer\FileSystems\File();
		
		if ($strName !== null) {
			$fileObj->setName($strName);
		}
		if ($strPath !== null) {
			$dirObj	= $this->getDirectory($strPath);
			$fileObj->setDirectory($dirObj);
			$dirObj->setChild($fileObj);
		}
		return $fileObj;
	}
	
	public function getDirectory($strPath=null)
	{
		if ($strPath !== null) {
			$strDirs	= array_filter(explode(DIRECTORY_SEPARATOR, $strPath));
			
			$pDir	= null;
			foreach ($strDirs as $strDir) {
				$dirObj		= new \MTS\Common\Data\Computer\FileSystems\Directory();
				$dirObj->setName($strDir);
				if ($pDir !== null) {
					$dirObj->setParent($pDir);
					$pDir->setChild($dirObj);
				}
				
				$pDir	= $dirObj;
			}

		} else {
			$dirObj		= new \MTS\Common\Data\Computer\FileSystems\Directory();
		}
		return $dirObj;
	}
	public function getProcessPipe($inputFile=null, $outputFile=null, $errorFile=null)
	{
		$procPipe	= new \MTS\Common\Data\Computer\FileSystems\ProcessPipe();
		if ($inputFile !== null) {
			$procPipe->setInputFile($inputFile);
		}
		if ($outputFile !== null) {
			$procPipe->setOutputFile($outputFile);
		}
		if ($errorFile !== null) {
			$procPipe->setErrorFile($errorFile);
		}
		return $procPipe;
	}
	
	
	public function getDirectoriesTool()
	{
		if (array_key_exists(__METHOD__, $this->_classStore) === false) {
			$this->_classStore[__METHOD__]	= new \MTS\Common\Tools\FileSystems\Directories();
		}
		return $this->_classStore[__METHOD__];
	}
	public function getFilesTool()
	{
		if (array_key_exists(__METHOD__, $this->_classStore) === false) {
			$this->_classStore[__METHOD__]	= new \MTS\Common\Tools\FileSystems\Files();
		}
		return $this->_classStore[__METHOD__];
	}
}