<?php
namespace MTS\Common\Tools\FileSystems;

class Directories
{
	public function isDirectoryObj($dirObj, $throw=false)
	{
		if ($dirObj instanceof \MTS\Common\Data\Computer\FileSystems\Directory) {
			return true;
		}
		if ($throw === false) {
			return false;
		} else {
			throw new \Exception(__METHOD__ . ">> Input Not Path Obj");
		}
	}
	public function isDirectory($dirObj, $throw=false)
	{
		$this->isDirectoryObj($dirObj, true);
		$isDir	= is_dir($dirObj->getPathAsString());
		if ($isDir === true) {
			return true;
		} else {
			if ($throw === false) {
				return false;
			} else {
				throw new \Exception(__METHOD__ . ">> Input Not a Directory");
			}
		}
	}
	public function create($dirObj)
	{
		$isDir	= $this->isDirectory($dirObj);
		if ($isDir === false) {
			
			$dirs	= array();
			$dirs[]	= $dirObj;
			while ($dirObj->getParent() !== null) {
				$dirObj		= $dirObj->getParent();
				$dirs[]		= $dirObj;
			}
			
			$dirs	= array_reverse($dirs);
			
			foreach ($dirs as $dirObj) {
				$isDir	= $this->isDirectory($dirObj);
				
				if ($isDir === false) {
					$valid	= mkdir($dirObj->getPathAsString());
					if ($valid === false) {
						throw new \Exception(__METHOD__ . ">> Failed to create directory: ".$dirObj->getPathAsString()."");
					}
				}
			}
		}
	}
	public function delete($dirObj)
	{
		//should delete recursively files and directories
		$isDir	= $this->isDirectory($dirObj);
		
		if ($isDir === true) {

			function removePath($path) {

				$items	= scandir($path);
				foreach ($items as $item) {
					$item	= trim($item);
					if ($item != "." && $item != "..") {
						$nPath	= $path . DIRECTORY_SEPARATOR . $item;
						$isDir	= is_dir($nPath);
						if ($isDir === true) {
							//recurse
							removePath($nPath);
						} else {
							//delete file
							$deleted	= @unlink($nPath);
							if ($deleted === false) {
								throw new \Exception(__METHOD__ . ">> Deleting Directory, Failed to Delete File: " . $nPath);
							}
						}
					}
				}
				
				//directory is now empty, delete it
				$deleted	= rmdir($path);
				if ($deleted === false) {
					throw new \Exception(__METHOD__ . ">> Deleting Directory, Failed to Delete Directory: " . $path);
				}
			}
			removePath($dirObj->getPathAsString());
		}
	}
	public function setMode($dirObj, $mode)
	{
		$this->isDirectory($dirObj, true);
	
		$valid	= chmod($dirObj->getPathAsString(), $mode);
		if ($valid === false) {
			throw new \Exception(__METHOD__ . ">> Failed to set mode. Directory name: " . $dirObj->getPathAsString());
		}
	}
}