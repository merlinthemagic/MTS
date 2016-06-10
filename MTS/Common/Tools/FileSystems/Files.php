<?php
//© 2016 Martin Madsen
namespace MTS\Common\Tools\FileSystems;

class Files
{
	public function isFileObj($fileObj, $throw=false)
	{
		if ($fileObj instanceof \MTS\Common\Data\Computer\FileSystems\File) {
			return true;
		}
		if ($throw === false) {
			return false;
		} else {
			throw new \Exception(__METHOD__ . ">> Input Not File Obj");
		}
	}
	public function isFile($fileObj, $throw=false)
	{
		$this->isFileObj($fileObj, true);
		$exist	= file_exists($fileObj->getPathAsString());
		
		if ($exist === true) {
			return true;
		} else {
			if ($throw === false) {
				return false;
			} else {
				throw new \Exception("File does not exist: ".$fileObj->getPathAsString()."");
			}
		}
	}
	public function create($fileObj)
	{
		if ($this->isFile($fileObj) === false) {
			if ($fileObj->getDirectory() !== null) {
				//create directories as needed
				\MTS\Factories::getFiles()->getDirectoriesTool()->create($fileObj->getDirectory());
			}
			
			$fh = fopen($fileObj->getPathAsString(),'w');
			if ($fh === false) {
				throw new \Exception(__METHOD__ . ">> Failed to open file for creation. File name: " . $fileObj->getPathAsString());
			} else {
				fclose($fh);
				if ($this->isFile($fileObj) === false) {
					//failed to create
					throw new \Exception(__METHOD__ . ">> Failed to Create File: " . $fileObj->getPathAsString());
				}
			}
		}
	}
	public function delete($fileObj)
	{
		if ($this->isFile($fileObj) === true) {
			$deleted	= unlink($fileObj->getPathAsString());
			if ($deleted === false) {
				throw new \Exception(__METHOD__ . ">> Failed to Delete for: " . $fileObj->getPathAsString());
			}
		}
	}
	public function appendContent($fileObj)
	{
		//add content implies the file exists already
		$this->isFile($fileObj, true);
		$dataLength	= strlen($fileObj->getContent());
		if ($dataLength > 0) {
			//returns the right count even when writing to pipe
			$bytesWritten	= file_put_contents($fileObj->getPathAsString(), $fileObj->getContent(), FILE_APPEND | LOCK_EX);
			if ($bytesWritten === false) {
				//failed to write, maybe file does not exist
				throw new \Exception(__METHOD__ . ">> Failed Write to: " . $fileObj->getPathAsString());
			} elseif ($bytesWritten != $dataLength) {
				throw new \Exception(__METHOD__ . ">> Partially Failed Write, Length Mismatch to: " . $fileObj->getPathAsString());
			}
		}
	}
	public function getSize($fileObj)
	{
		$this->isFile($fileObj, true);
		$byteSize	= @filesize($fileObj->getPathAsString());
		if ($byteSize === false) {
			//maybe file does not exist
			throw new \Exception(__METHOD__ . ">> Failed Get Size for: " . $fileObj->getPathAsString());
		} else {
			return $byteSize;
		}
	}
	public function setContent($fileObj)
	{
		$exist	= $this->isFile($fileObj, false);
		if ($exist === false) {
			$this->create($fileObj);
		} else {
			//empty the file
			$f = @fopen($fileObj->getPathAsString(), "r+");
			if ($f !== false) {
				ftruncate($f, 0);
				fclose($f);
			} else {
				throw new \Exception(__METHOD__ . ">> Failed to open file to truncate: " . $fileObj->getPathAsString());
			}
		}
		
		if ($fileObj->getContent() !== null) {
			$this->appendContent($fileObj);
		}
	}
	public function getContent($fileObj, $startByte=null, $endByte=null)
	{
		$this->isFile($fileObj, true);
		$fileObj->setContent(null);
	
		if ($startByte === null && $endByte === null) {
			$content	= file_get_contents($fileObj->getPathAsString());
			$fileObj->setContent($content);
		} else {
				
			$fileSize	= $this->getSize($fileObj);
	
			if ($startByte === null) {
				$startByte	= 0;
			}
			if ($endByte === null) {
				$endByte	= $fileSize;
			}
	
			if ($endByte > $fileSize) {
				$endByte	= $fileSize;
			}
			if ($startByte > $endByte) {
				$startByte	= $endByte;
			}
			$endByte	= intval($endByte);
			$startByte	= intval($startByte);
			$requestLen	= ($endByte - $startByte);
	
			if ($endByte > $startByte) {
				
				$fh = fopen($fileObj->getPathAsString(),'r');
				if ($fh === false) {
					throw new \Exception(__METHOD__ . ">> Failed to open file for read. File name: " . $fileObj->getPathAsString());
				} else {
					if ($startByte == 0) {
						$contents	= fread($fh, $requestLen);
					} else {	
						fseek($fh, $startByte);
						$contents 	= fread($fh, $requestLen);
					}
					fclose($fh);
					$fileObj->setContent($contents);
				}
					
			}
		}
	}
}