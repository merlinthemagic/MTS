<?php
//© 2016 Martin Madsen
namespace MTS\Common\Devices\Browsers;

interface BrowserInterface
{
	public function setURL($windowObj, $url);
	public function getDom($windowObj);
	public function screenshot($windowObj, $format);
	public function focusElement($windowObj, $selector);
	public function clickElement($windowObj, $selector);
	public function mouseEventOnElement($windowObj, $selector, $event);
	public function sendKeyPresses($windowObj, $keys, $modifiers);
	public function loadJS($windowObj, $scriptData);
	public function callJSFunction($windowObj, $functionName);
}