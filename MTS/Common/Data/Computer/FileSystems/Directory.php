<?php
//© 2016 Martin Madsen
namespace MTS\Common\Data\Computer\FileSystems;

class Directory
{
	protected $_name=null;
	protected $_parent=null;
	protected $_children=array();

	public function setName($name)
	{
		$this->_name	= $name;
	}
	public function getName()
	{
		return $this->_name;
	}
	public function setParent($dirObj)
	{
		$this->_parent	= $dirObj;
	}
	public function getParent()
	{
		return $this->_parent;
	}
	public function setChild($obj)
	{
		//can be both files and directories
		$this->_children[]	= $obj;
	}
	public function getChildren()
	{
		return $this->_children;
	}
	public function getPathAsString()
	{
		$strPath	= "";
		$pDir		= $this->getParent();
		if ($pDir !== null) {
			$strPath	.= $pDir->getPathAsString();
		}
		
		if (DIRECTORY_SEPARATOR == "/") {
			$strPath	.= DIRECTORY_SEPARATOR . $this->getName();
		} else {
			//windows
			if ($pDir !== null) {
				$strPath	.= DIRECTORY_SEPARATOR . $this->getName();
			} else {
				$strPath	.= $this->getName();
			}
		}

		return $strPath;
	}
}