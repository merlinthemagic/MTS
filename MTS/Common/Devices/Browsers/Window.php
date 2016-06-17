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
	
	private $_winScrollTop=0;
	private $_winScrollLeft=0;

	private $_rasterTop=0;
	private $_rasterLeft=0;
	private $_rasterWidth=1920;
	private $_rasterHeight=1080;
	
	private $_loadImages=true;
	private $_userAgent=null;

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
		
		$rSize	= $this->getRasterSize();
		if (($rSize['top'] + $rSize['height']) > $this->_winHeight  || ($rSize['left'] + $rSize['width']) > $this->_winWidth) {
			//the raster area s bigger than the size of the window
			$this->setRasterSize(0, 0, $width, $height);
		}
	}
	public function getSize()
	{
		return array('width' => $this->_winWidth, 'height' => $this->_winHeight);
	}
	public function setScrollPosition($top, $left)
	{
		$this->_winScrollTop	= intval($top);
		$this->_winScrollLeft	= intval($left);
	}
	public function getScrollPosition()
	{
		return array('top' => $this->_winScrollTop, 'left' => $this->_winScrollLeft);
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
	public function close()
	{
		$this->getBrowser()->closeWindow($this);
	}
	public function setLoadImages($bool)
	{
		$this->_loadImages	= $bool;
	}
	public function getLoadImages()
	{
		return $this->_loadImages;
	}
	public function setUserAgent($agentName)
	{
		//here are short hand agents, for more choices see here: http://www.useragentstring.com/
		$agent		= strtolower($agentName);
		if ($agent == "firefox47") {
			$agentName = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0";
		}
		
		$this->_userAgent	= $agentName;
	}
	public function getUserAgent()
	{
		return $this->_userAgent;
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
	public function getCookies()
	{
		return $this->getBrowser()->getCookies($this);
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
	public function getSelectorExists($selector)
	{
		return $this->getBrowser()->getSelectorExists($this, $selector);
	}
	public function getElement($selector)
	{
		return $this->getBrowser()->getElement($this, $selector);
	}
	public function getDocument()
	{
		return $this->getBrowser()->getDocument($this);
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
		if (isset($this->_children[$childObj->getUUID()])) {
			//remove self as parent
			$this->_children[$childObj->getUUID()]->setParent(null);
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