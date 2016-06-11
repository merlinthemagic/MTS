<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Browsers;

class Window
{
	private $_browserObj=null;
	private $_uuid=null;
	private $_parent=null;
	private $_children=array();
	private $_winWidth=1920;
	private $_winHeight=1080;

	private $_rasterTop=0;
	private $_rasterLeft=0;
	private $_rasterWidth=1920;
	private $_rasterHeight=1080;
	
	
	public function __construct()
	{
		//any time a selector is required they follow the pattern laid out here:
		//https://www.w3.org/TR/css3-selectors/#selectors
		
		//i.e. if you wish to select the ID=merlin-login on a page the selector would be: "[id=merlin-login]"
	}
	
	public function setSize($width, $height)
	{
		$this->_winWidth	= intval($width);
		$this->_winHeight	= intval($height);
	}
	public function getSize()
	{
		return array('width' => $this->_winWidth, 'height' => $this->_winHeight);
	}
	public function setRasterSize($top, $left, $width, $height)
	{
		//size of the window we want to take a screenshot off
		$this->_rasterTop		= intval($top);
		$this->_rasterLeft		= intval($left);
		$this->_rasterWidth		= intval($width);
		$this->_rasterHeight	= intval($height);
	}
	public function getRasterSize()
	{
		return array('top' => $this->_rasterTop, 'left' => $this->_rasterLeft, 'width' => $this->_rasterWidth, 'height' => $this->_rasterHeight);
	}
	public function setURL($strUrl)
	{
		return $this->getBrowser()->setURL($this, $strUrl);
	}
	public function screenshot($format='png')
	{
		$changedRs	= false;
		$rSize		= $this->getRasterSize();
		if ($rSize['top'] == 0 && $rSize['left'] == 0 && $rSize['width'] == 0 && $rSize['height'] == 0) {
			//area cannot be all zeros, we need something to take a picture off
			$changedRs	= true;
			$winSize	= $this->getSize();
			$this->setRasterSize(0, 0, $winSize['width'], $winSize['height']);
		}
		
		$imageData	= $this->getBrowser()->screenshot($this, $format);
		
		if ($changedRs === true) {
			$this->setRasterSize($rSize['top'], $rSize['left'], $rSize['width'], $rSize['height']);
		}
		
		return $imageData;
	}
	public function getDom()
	{
		return $this->getBrowser()->getDom($this);
	}
	public function focusElement($selector)
	{
		return $this->getBrowser()->focusElement($this, $selector);
	}
	public function sendKeyPresses($keys, $modifiers=array())
	{
		//keys can be string or array
		return $this->getBrowser()->sendKeyPresses($this, $keys, $modifiers);
	}
	public function clickElement($selector)
	{
		//can click hidden buttons and links
		return $this->getBrowser()->clickElement($this, $selector);
	}
	public function getElement($selector)
	{
		return $this->getBrowser()->getElement($this, $selector);
	}
	public function mouseEventOnElement($selector, $event)
	{
		return $this->getBrowser()->mouseEventOnElement($this, $selector, $event);
	}
	public function loadJS($scriptData)
	{
		return $this->getBrowser()->loadJS($this, $scriptData);
	}
	public function callJSFunction($functionName)
	{
		return $this->getBrowser()->callJSFunction($this, $functionName);
	}
	public function getParent()
	{
		return $this->_parent;
	}
	public function setParent($parentObj)
	{
		//should only be called from the parent
		$this->_parent = $parentObj;
	}
	public function getChild($uuid)
	{
		if (isset($this->_children[$uuid])) {
			return $this->_children[$uuid];
		} else {
			return false;
		}
	}
	public function getChildren()
	{
		return $this->_children;
	}
	public function setChild($childObj)
	{
		$this->_children[$childObj->getUUID()] = $childObj;
		$childObj->setParent($this);
	}
	public function removeChild($childObj)
	{
		//should only be called directly from browser class
		//or a endless loop will ensue
		if (isset($this->_children[$childObj->getUUID()])) {
			unset($this->_children[$childObj->getUUID()]);
		}
	}
	public function setBrowser($browserObj)
	{
		$this->_browserObj	= $browserObj;
	}
	public function getBrowser()
	{
		return $this->_browserObj;
	}
	public function getUUID()
	{
		if ($this->_uuid === null) {
			$this->setUUID(uniqid());
		}
		return $this->_uuid;
	}
	public function setUUID($uuid)
	{
		$this->_uuid	= $uuid;
	}
}