<?php
//© 2016 Martin Madsen
namespace MTS\Common\Data\Computer\FileSystems;

class File
{
	protected $_name=null;
	protected $_directory=null;
	protected $_content=null;

	public function setName($name)
	{
		$this->_name	= $name;
	}
	public function getName()
	{
		return $this->_name;
	}
	public function setDirectory($dirObj)
	{
		$this->_directory	= $dirObj;
	}
	public function getDirectory()
	{
		return $this->_directory;
	}
	public function setContent($content)
	{
		$this->_content	= $content;
	}
	public function getContent()
	{
		return $this->_content;
	}
	
	public function getPathAsString()
	{
		$strPath	= "";
		$dir		= $this->getDirectory();
		if ($dir !== null) {
			$strPath	.= $dir->getPathAsString();
		}
		$strPath	.= DIRECTORY_SEPARATOR . $this->getName();
	
		return $strPath;
	}
}