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
		//the device running ROS that you wish to test against
		if (array_key_exists(__METHOD__, self::$_classStore) === false) {
	
			//password may be empty
			if (
				self::$genericHostname != ""
				&& self::$genericUsername != ""
				&& self::$genericConnType != ""
				&& self::$genericConnPort != ""
			) {
				$deviceObj	= \MTS\Factories::getDevices()->getRemoteHost(self::$genericHostname)->setConnectionDetail(self::$genericUsername, self::$genericPassword, self::$genericConnType, self::$genericConnPort);
			} else {
				$deviceObj	= null;
			}
				
			if (self::$genericCache === true) {
				self::$_classStore[__METHOD__]	= $deviceObj;
			} else {
				return $deviceObj;
			}
		}
		return self::$_classStore[__METHOD__];
	}
	public static function getROSDevice()
	{
		//the device running ROS that you wish to test against
		if (array_key_exists(__METHOD__, self::$_classStore) === false) {
	
			//password may be empty
			if (
			self::$rosHostname != ""
					&& self::$rosUsername != ""
							&& self::$rosConnType != ""
									&& self::$rosConnPort != ""
											) {
				$username	= self::$rosUsername;
				if (self::$rosConnType == "ssh") {
					//much faster if we add the correct terminal options before login
					$termOptions	= \MTS\Factories::getActions()->getRemoteConnectionsSsh()->getMtTermOptions();
					$username		= $username . "+" . $termOptions;
				}
				$deviceObj	= \MTS\Factories::getDevices()->getRemoteHost(self::$rosHostname)->setConnectionDetail($username, self::$rosPassword, self::$rosConnType, self::$rosConnPort);
	
			} else {
				$deviceObj	= null;
			}
				
			if (self::$rosCache === true) {
				self::$_classStore[__METHOD__]	= $deviceObj;
			} else {
				return $deviceObj;
			}
		}
		return self::$_classStore[__METHOD__];
	}
}