<?php
//© 2016 Martin Madsen
namespace MTS\Common\Data\Computer\OperatingSystems\Mikrotik;

class Base
{
	protected $_mVersion=null;
	protected $_architecture=null;
	
	public function getType()
	{
		return 'Mikrotik';
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