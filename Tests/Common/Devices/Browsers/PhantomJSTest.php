<?php
//© 2016 Martin Madsen
class PhantomJSTest extends PHPUnit_Framework_TestCase
{
	public function test_pjsFunctions()
	{
		$hasInternet	= \MTS\Factories::getActions()->getLocalPhpEnvironment()->isConnectedToInternet();
		if ($hasInternet === true) {
			
			$browserObj		= \MTS\Factories::getDevices()->getLocalHost()->getBrowser('phantomjs');
			$this->assertInstanceOf("MTS\Common\Devices\Browsers\PhantomJS", $browserObj);
			
			$windowObj			= $browserObj->getNewWindow();
			$this->assertInstanceOf("MTS\Common\Devices\Browsers\Window", $windowObj);
				
			$testUrl	= "http://www.wikipedia.org";
			$result		= $windowObj->setUrl($testUrl);
			$this->assertEmpty($result);

			$testWidth	= 640;
			$testHeight	= 480;
			$result		= $windowObj->setSize($testWidth, $testHeight);
			$this->assertEmpty($result);
			
			$result		= $windowObj->getSize();
			$this->assertInternalType("array", $result);
			$this->assertEquals($result['width'], $testWidth);
			$this->assertEquals($result['height'], $testHeight);
			
			$testScrollTop		= 50;
			$testScrollLeft		= 10;
			$result				= $windowObj->setScrollPosition($testScrollTop, $testScrollLeft);
			$this->assertEmpty($result);
				
			$result		= $windowObj->getScrollPosition();
			$this->assertInternalType("array", $result);
			$this->assertEquals($result['top'], $testScrollTop);
			$this->assertEquals($result['left'], $testScrollLeft);
			
			$testRasterTop		= 50;
			$testRasterLeft		= 10;
			$testRasterWidth	= 50;
			$testRasterHeight	= 100;
			$result				= $windowObj->setRasterSize($testRasterTop, $testRasterLeft, $testRasterWidth, $testRasterHeight);
			$this->assertEmpty($result);
			
			$result		= $windowObj->getRasterSize();
			$this->assertInternalType("array", $result);
			$this->assertEquals($result['top'], $testRasterTop);
			$this->assertEquals($result['left'], $testRasterLeft);
			$this->assertEquals($result['width'], $testRasterWidth);
			$this->assertEquals($result['height'], $testRasterHeight);
				
			$testLoadImages		= false;
			$result				= $windowObj->setLoadImages($testLoadImages);
			$this->assertEmpty($result);
			
			$result		= $windowObj->getLoadImages();
			$this->assertEquals($testLoadImages, $result);
			
			$testUserAgent		= "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0";
			$result				= $windowObj->setUserAgent($testUserAgent);
			$this->assertEmpty($result);
				
			$result		= $windowObj->getUserAgent();
			$this->assertEquals($testUserAgent, $result);
			
			$result				= $windowObj->screenshot('png');
			$this->assertInternalType("string", $result);
			
			$pngStart  = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a";
			$result		= substr($result, 0, 8);
			$this->assertEquals($pngStart, $result);
			
			$result				= $windowObj->screenshot('jpeg');
			$this->assertInternalType("string", $result);
				
			$jpegStart  = "\xFF\xD8\xFF";
			$result		= substr($result, 0, 3);
			$this->assertEquals($jpegStart, $result);
			
			$result			= $windowObj->getDom();
			$this->assertInternalType("string", $result);

			$result			= $windowObj->getDocument();
			$this->assertInternalType("array", $result);
			
			$result			= $windowObj->getCookies();
			$this->assertInternalType("array", $result);
			
			$funcName		= "myHelloWorld";
			$scriptReturn	= "HelloWorld";
			$script = "function ".$funcName."() {
               				return '".$scriptReturn."';
          				}";
			$result				= $windowObj->loadJS($script);
			$this->assertEmpty($result);
			
			$result			= $windowObj->callJSFunction($funcName);
			$this->assertEquals($scriptReturn, $result);
			
			//selector will change as wikipedia changes their site
			$selector		= "[id=searchInput]";
			$result			= $windowObj->getSelectorExists($selector);
			$this->assertInternalType("bool", $result);
			if ($result === true) {
				//if element exists test the element functions
				$result			= $windowObj->clickElement($selector);
				$this->assertEmpty($result);
				
				$result			= $windowObj->mouseEventOnElement($selector, 'down');
				$this->assertEmpty($result);
				
				$result			= $windowObj->mouseEventOnElement($selector, 'move');
				$this->assertEmpty($result);
				
				$result			= $windowObj->mouseEventOnElement($selector, 'up');
				$this->assertEmpty($result);
				
				$result			= $windowObj->mouseEventOnElement($selector, 'rightdoubleclick');
				$this->assertEmpty($result);
				
				$result			= $windowObj->mouseEventOnElement($selector, 'leftdoubleclick');
				$this->assertEmpty($result);
				
				$result			= $windowObj->mouseEventOnElement($selector, 'rightclick');
				$this->assertEmpty($result);

				$result			= $windowObj->mouseEventOnElement($selector, 'leftclick');
				$this->assertEmpty($result);
				
				$result			= $windowObj->focusElement($selector);
				$this->assertEmpty($result);
				
				$testStr		= "Nikola";
				$result			= $windowObj->sendKeyPresses($testStr);
				$this->assertEmpty($result);
				
				$result			= $windowObj->getElement($selector);
				$this->assertInternalType("array", $result);
				$this->assertEquals($testStr, $result['value']);
				
				$testStr2		= "Tesla";
				$testMods		= array("shift");
				$result			= $windowObj->sendKeyPresses($testStr2, $testMods);
				$this->assertEmpty($result);
				
				$result			= $windowObj->getElement($selector);
				$this->assertInternalType("array", $result);
				$this->assertEquals($testStr . $testStr2, $result['value']);
			}

			//completed
			$browserObj->terminate();
			
		} else {
			//what can we test without internet?
		}
	}
}