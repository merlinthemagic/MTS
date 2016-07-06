<?php
//© 2016 Martin Madsen

class MtsUnitTestDevices
{
	protected static $_classStore=array();
	
	//configure device
	public static $hostname=null;
	public static $username=null;
	public static $password=null;
	public static $connType=null;
	public static $connPort=null;
	public static $deviceCache=null;
	
	//test if we change user in the shell, i.e. obtain root shell from the default user
	public static $switchUsername=null;
	public static $switchPassword=null;

	public static function getDevice()
	{
		$deviceObj	= null;
		//password may be empty
		if (
			self::$hostname != ""
			&& self::$username != ""
			&& self::$connType != ""
			&& self::$connPort != ""
		) {
		
			if (self::$deviceCache === false) {
				//destroy existing device if there is one
				if (array_key_exists(__METHOD__, self::$_classStore) === true) {
					self::$_classStore[__METHOD__]->getShell()->terminate();
					unset(self::$_classStore[__METHOD__]);
				}
			}
			
			if (array_key_exists(__METHOD__, self::$_classStore) === false) {
				self::$_classStore[__METHOD__]	= \MTS\Factories::getDevices()->getRemoteHost(self::$hostname)->setConnectionDetail(self::$username, self::$password, self::$connType, self::$connPort);
			}
				
			$deviceObj	= self::$_classStore[__METHOD__];
		}
		
		return $deviceObj;
	}
}