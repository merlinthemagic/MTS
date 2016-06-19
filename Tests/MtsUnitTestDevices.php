<?php
//© 2016 Martin Madsen

class MtsUnitTestDevices
{
	protected static $_classStore=array();
	
	//configure generic host
	public static $genericHostname="";
	public static $genericUsername="";
	public static $genericPassword="";
	public static $genericConnType="";
	public static $genericConnPort="";
	public static $genericCache=false;
	
	//configure ros device
	public static $rosHostname="";
	public static $rosUsername="";
	public static $rosPassword="";
	public static $rosConnType="";
	public static $rosConnPort="";
	public static $rosCache=false;
	
	public static function getGenericDevice()
	{
		$deviceObj	= null;
		//password may be empty
		if (
			self::$genericHostname != ""
			&& self::$genericUsername != ""
			&& self::$genericConnType != ""
			&& self::$genericConnPort != ""
		) {
		
			if (self::$genericCache === false) {
				//destroy existing device if there is one
				if (array_key_exists(__METHOD__, self::$_classStore) === true) {
					self::$_classStore[__METHOD__]->getShell()->terminate();
					unset(self::$_classStore[__METHOD__]);
				}
			}
			
			if (array_key_exists(__METHOD__, self::$_classStore) === false) {
				self::$_classStore[__METHOD__]	= \MTS\Factories::getDevices()->getRemoteHost(self::$genericHostname)->setConnectionDetail(self::$genericUsername, self::$genericPassword, self::$genericConnType, self::$genericConnPort);
			}
				
			$deviceObj	= self::$_classStore[__METHOD__];
		}
		
		return $deviceObj;
	}
	public static function getROSDevice()
	{
		$deviceObj	= null;
		//password may be empty
		if (
			self::$rosHostname != ""
			&& self::$rosUsername != ""
			&& self::$rosConnType != ""
			&& self::$rosConnPort != ""
		) {

			if (self::$rosCache === false) {
				//destroy existing device if there is one
				if (array_key_exists(__METHOD__, self::$_classStore) === true) {
					self::$_classStore[__METHOD__]->getShell()->terminate();
					unset(self::$_classStore[__METHOD__]);
				}
			}
			
			if (array_key_exists(__METHOD__, self::$_classStore) === false) {
				$username	= self::$rosUsername;
				if (self::$rosConnType == "ssh") {
					//much faster if we add the correct terminal options before login
					$termOptions	= \MTS\Factories::getActions()->getRemoteConnectionsSsh()->getMtTermOptions();
					$username		= $username . "+" . $termOptions;
				}
				self::$_classStore[__METHOD__]	= \MTS\Factories::getDevices()->getRemoteHost(self::$rosHostname)->setConnectionDetail($username, self::$rosPassword, self::$rosConnType, self::$rosConnPort);
			}
			
			$deviceObj	= self::$_classStore[__METHOD__];
		}
		
		return $deviceObj;
	}
}