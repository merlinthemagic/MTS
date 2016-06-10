<?php
//© 2016 Martin Madsen
namespace MTS\Common\Data\Computer\OperatingSystems\Linux;

class Base
{
	protected $_mVersion=null;
	protected $_architecture=null;
	
	public function getType()
	{
		return 'Linux';
	}
	public function setMajorVersion($version)
	{
		$this->_mVersion	= $version;
	}
	public function getMajorVersion()
	{
		return $this->_mVersion;
	}
	public function setArchitecture($arch)
	{
		$this->_architecture	= $arch;
	}
	public function getArchitecture()
	{
		return $this->_architecture;
	}
}