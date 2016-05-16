<?php
//© 2016 Martin Madsen
namespace MTS;

class Factories
{
	private static $_classStore=array();
	
	//USE: 
	//$aFact		= \MTS\Factories::$METHOD_NAME();
	
	public static function getDevices()
	{
		if (array_key_exists(__METHOD__, self::$_classStore) === false) {
			self::$_classStore[__METHOD__]	= new \MTS\Factories\Devices();
		}
		return self::$_classStore[__METHOD__];
	}
	public static function getActions()
	{
		if (array_key_exists(__METHOD__, self::$_classStore) === false) {
			self::$_classStore[__METHOD__]	= new \MTS\Factories\Actions();
		}
		return self::$_classStore[__METHOD__];
	}
	public static function getFiles()
	{
		if (array_key_exists(__METHOD__, self::$_classStore) === false) {
			self::$_classStore[__METHOD__]	= new \MTS\Factories\Files();
		}
		return self::$_classStore[__METHOD__];
	}
	public static function getTime()
	{
		if (array_key_exists(__METHOD__, self::$_classStore) === false) {
			self::$_classStore[__METHOD__]	= new \MTS\Factories\Time();
		}
		return self::$_classStore[__METHOD__];
	}
}