<?php
//© 2016 Martin Madsen
namespace MTS\Factories;

class Devices
{
	protected $_classStore=array();

	public function getLocalHost()
	{
		//dont cache, each call should return new instance
		return new \MTS\Common\Devices\Types\Localhost();
	}
	public function getRemoteHost($hostname=null)
	{
		//dont cache, each call should return new instance
		$rHost	= new \MTS\Common\Devices\Types\Remotehost();
		if ($hostname !== null) {
			$rHost->setHostname($hostname);
		}
		return $rHost;
	}
	public function getOsObj($osType, $osName, $osArch=null, $majorVersion=null)
	{
		if ($osType != "" && $osName != "") {
			
			$osObj		= null;
			$osType		= strtolower(trim($osType));
			$osName		= strtolower(trim($osName));

			if ($osType == 'linux') {
				if ($osName == 'centos') {
					$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\CentOSBase();
				} elseif ($osName == 'red hat') {
					$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\RHELBase();
				} elseif ($osName == 'debian') {
					$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\DebianBase();
				} elseif ($osName == 'ubuntu') {
					$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\UbuntuBase();
				} elseif ($osName == 'arch') {
					$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Linux\ArchBase();
				}
			
			} elseif ($osType == 'windows') {
				if ($osName == 'windows') {
					$osObj	= new \MTS\Common\Data\Computer\OperatingSystems\Microsoft\Windows();
				}
			}
			if ($osObj !== null) {
				if ($osArch != "") {
					$osObj->setArchitecture($osArch);
				}
				if ($majorVersion != "") {
					$osObj->setMajorVersion($majorVersion);
				}
				
				return $osObj;
				
			} else {
				throw new \Exception(__METHOD__ . ">> OS Type: " . $osType . " and name:" . $osName . ", not supported");
			}
			
		} else {
			throw new \Exception(__METHOD__ . ">> Invalid Input");
		}
	}
}