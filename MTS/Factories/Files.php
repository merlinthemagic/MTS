<?php
//© 2016 Martin Madsen
namespace MTS\Factories;

class Files
{
	protected $_classStore=array();
	
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
	
	//Tools
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
	
	//vendor Files
	public function getVendorFile($name)
	{
		$name	= strtolower($name);
		if ($name == "pjswindows64") {
			$vendorPath		= $this->getDirectory(MTS_BASE_PATH . "MTS". DIRECTORY_SEPARATOR ."Common". DIRECTORY_SEPARATOR ."Devices". DIRECTORY_SEPARATOR ."VendorData". DIRECTORY_SEPARATOR ."phantomJS");
			return $this->getFile("PJSWindows.exe", $vendorPath->getPathAsString());
		} elseif ($name == "pjswindows32") {
			$vendorPath		= $this->getDirectory(MTS_BASE_PATH . "MTS". DIRECTORY_SEPARATOR ."Common". DIRECTORY_SEPARATOR ."Devices". DIRECTORY_SEPARATOR ."VendorData". DIRECTORY_SEPARATOR ."phantomJS");
			return $this->getFile("PJSWindows.exe", $vendorPath->getPathAsString());
		} elseif ($name == "pjslinux64") {
			$vendorPath		= $this->getDirectory(MTS_BASE_PATH . "MTS". DIRECTORY_SEPARATOR ."Common". DIRECTORY_SEPARATOR ."Devices". DIRECTORY_SEPARATOR ."VendorData". DIRECTORY_SEPARATOR ."phantomJS");
			return $this->getFile("PJSLinux64", $vendorPath->getPathAsString());
		} elseif ($name == "pjslinux32") {
			$vendorPath		= $this->getDirectory(MTS_BASE_PATH . "MTS". DIRECTORY_SEPARATOR ."Common". DIRECTORY_SEPARATOR ."Devices". DIRECTORY_SEPARATOR ."VendorData". DIRECTORY_SEPARATOR ."phantomJS");
			return $this->getFile("PJSLinux32", $vendorPath->getPathAsString());
		} elseif ($name == "pjsctrl") {
			$vendorPath		= $this->getDirectory(MTS_BASE_PATH . "MTS". DIRECTORY_SEPARATOR ."Common". DIRECTORY_SEPARATOR ."Devices". DIRECTORY_SEPARATOR ."VendorData". DIRECTORY_SEPARATOR ."phantomJS");
			return $this->getFile("PJSCtrl.js", $vendorPath->getPathAsString());
		} elseif ($name == "psv1ctrl") {
			$vendorPath		= $this->getDirectory(MTS_BASE_PATH . "MTS". DIRECTORY_SEPARATOR ."Common". DIRECTORY_SEPARATOR ."Devices". DIRECTORY_SEPARATOR ."VendorData". DIRECTORY_SEPARATOR ."PowerShell");
			return $this->getFile("mtsPsInit.ps1", $vendorPath->getPathAsString());
		} else {
			throw new \Exception(__METHOD__ . ">> Vendor File Name: ".$name.", not defined");
		}
	}
}