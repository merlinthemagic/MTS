## The Browser:
Ever needed to login to a webpage, navigate to a specific menu and retrive content and the page relies on AJAX?
I have, tons of times, let me give you one example. 

A major retailer in the US has an API for their vendors to retrive reports.
The authentication is done by a token that can be renewed for upto one year, but every new years eve the token must be updated manually,
and that process could only be done via a web page. Its a process that always causes a hickup in an otherwise automated reporting system.
Its always the one off, once a year processes that do.

With this browser that process has been automated. Three weeks before new years eve, the system does a dry run to make sure the portal webpage has not changed.
If it changed (has not in the last 5 years) the staff gets an alert so they can update the process, otherwise the system automatically updates the token at 3am on new years while we are popping champagne.

Not a huge problem, but 15 minutes of coding and the system is truely automated.

This component would not be possible without the awesome work done by the people who built <a href="http://phantomjs.org/">PhantomJS</a>.
This project simply wraps their work so it is easy for you to access using PHP. 

### Basic use:

This package creates a instance of PhantomJS, you can then open a website and execute standard functions against it through PHP.
You start by following the installation instructions at the very top of this Readme, then instantiate a browser.

```php
//Some websites are either far away or just slow, so it is a good idea to up the allowed execution time.
ini_set('max_execution_time', 120);

//Get a new browser window:
$myUrl			= "https://www.wikipedia.org/";
$windowObj		= \MTS\Factories::getDevices()->getLocalHost()->getBrowser('phantomjs')->getNewWindow($myUrl);
```

$windowObj now contains a browser window with wikipedias website loaded.

Lets do a search on wikipedia. Whenever we wish to manipulate an element we do so by using a css selector to identify it. Its really easy i.e.:
if we want an element with id='mySearchBox' the selector would be: [id=mySearchBox]. Basically [attribute=value].
Another example of a selector: a.myClass:nth-of-type(2) this selector takes all hyperlinks in class "myClass", then picks the second one.
For more information see this <a href="https://www.w3.org/TR/css3-selectors/#selectors">article</a>.

```php
//left click on the search input box (it has id=searchInput):
$windowObj->mouseEventOnElement("[id=searchInput]", 'leftclick');

//Type the search string we want to perform:
$windowObj->sendKeyPresses("Nikola Tesla");

//Press enter. Note special keys must be inputted as an array, while characters / numbers are inputted as a string
$windowObj->sendKeyPresses(array("Enter"));
```

The page is now displaying the search result. Lets do a screen shot to make sure:

```php
//perform a screenshot:
$screenshotData	= $windowObj->screenshot();

//render it:
echo '<img src="data:image/png;base64,' . base64_encode($screenshotData) . '" />';
```

See "Window Methods" below for a complete list of examples

### How It Works:
PhantomJS is executed and the stdIn / stdOut are used to send and receive JSON encoded commands.
The JS file that is executed by PhantomJS is constantly checking its stdIn to see if any new commands have arrived.
Once a command is received the action is completed and the result returned.

You only have to worry about opening a page and manipulating it.

### Window Methods:

Set the size of the window (in pixels):
```php
$width	= 640;
$height	= 480;
$windowObj->setSize($width, $height);
```

Set the area of the window you want to screenshot (in pixels):
```php
$top	= 0;
$left	= 0;
$width	= 640;
$height	= 480;
$windowObj->setRasterSize($top, $left, $width, $height);
```

Take a screenshot of the window:

Accepts one argument which determines the image format. By default "png". All valid options: "png", "jpeg".
```php
$imageData	= $windowObj->screenshot();
```

Close window:
```php
$windowObj->close();
```
Note: This will also close any child windows

Get the DOM:
```php
//get the HTML of the current page:
$domData	= $windowObj->getDom();
```

Place cursor in a particular input element:
```php
$selector	= "[id=someElementId]";
$windowObj->focusElement($selector);
```

Does a particular selector exist:
```php
$selector	= "[id=someElementId]";
$exists		= $windowObj->getSelectorExists($selector);
//true if exists, else false
```

Type with the keyboard. Accepts two arguments: Keys to press and modifiers.

Characters and numbers are accepted as a string. Special keys must be set as array, special keys:
```php
$keys	= array('Enter', 'Tab', 'Space', 'Backspace', 'Delete', 'Up', 'Down', 'Left', 'Right', 'Pageup', 'Pagedown', 'Numlock', 'F1', 'F2', 'F3', 'F4', 'F5', 'F6', 'F7', 'F8', 'F9', 'F10', 'F11', 'F12', 'BraceLeft', 'BraceRight', 'BracketLeft', 'BracketRight');
```
Modifiers must  be set as array, modifier keys:
```php
$modifiers	= array('Alt', 'Shift', 'Ctrl', 'Meta', 'Keypad');
```
Examples:
```php
//Example 1, send a string of characters
$keys	= "My Search Key Words";
$windowObj->sendKeyPresses($keys);

//Example 2, send a string of characters while holding down "shift". 
$keys		= "My Search Key Words";
$modifiers	= array("shift");
$windowObj->sendKeyPresses($keys, $modifiers);

//Example 3, press enter
$keys	= array('Enter');
$windowObj->sendKeyPresses($keys);

```

Perform a mouse event on an element:

Valid events: 
"up", "down", "move", "leftclick", "leftdoubleclick", "rightclick", "rightdoubleclick"
```php
//left click an element
$selector	= "[id=someElementId]";
$event		= "leftclick";
$windowObj->mouseEventOnElement($selector, $event);
```
Note: Do not use this method to click hyperlinks, use clickElement() instead.
mouseEventOnElement() can only click on elements that have 2D size, a hyperlink is just a line and has no area to click.

Click on an element:
```php
$selector	= "[id=someElementId]";
$windowObj->clickElement($selector);
```

Load some custom JavaScript in the window:
```php
$scriptData	= "function myHelloWorld() {
		   		return 'Hello World';
		   }";
		   
$windowObj->loadJS($scriptData);
```

Call a JavaScript function:
```php
//$funcReturn will contain a string with the return from the function.
$funcReturn = $windowObj->callJSFunction("myHelloWorld");
```
Note: Only content that can be serialize by Json can be returned (JSON.stringify(data), will help you), no objects.

Get all cookies from a page:
```php
//returns array of cookies
$cookies	= $windowObj->getCookies();
```

Get details of an element i.e. value:
```php
//limited currently, will get more detail over time
//returns array
$selector	= "[id=someElementId]";
$eleDetails	= $windowObj->getElement($selector);
```

Get details of the document i.e. height and width:
```php
//limited currently, will get more detail over time
//returns array
$docDetails	= $windowObj->getDocument();
```

Set the url in the window:
```php
//Will load the URL in the window
$myUrl	= "http://www.google.com";
$windowObj->setURL($myUrl);
```

Get the current url in the window:
```php
//returns the current URL as a string.
$strUrl		= $windowObj->getURL();
```
Note: It is the CURRENT url that is returned, so if you submitted a form the URL may not be the same as you originally set.

Set the User Agent:
```php
$agentName	= "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0";
$windowObj->setUserAgent($agentName);
```

Set the scroll position in the window (in pixels):
```php
//scroll down the page 500px
$top	= 500;
$left	= 0;
$windowObj->setScrollPosition($top, $left);
```

Set if images should be loaded:
```php
//default is true, but setting to false will speed up loading by omitting images
$bool	= false;
$windowObj->setLoadImages($bool);
```

If the window spawned a popup or another window:
```php
$childWindowObjs	= $windowObj->getChildren();
if (count(childWindowObjs) > 0) {
	//the child window can be used just like a regular window
	$childWindowObj		= current($childWindowObjs);
	//i.e. you can take a screen shot
	$childImageData		= $childWindowObj->screenshot();
}
```
Note: If you execute setURL() on the parent window, all child windows will be closed automatically

##### Debugging:

Figuring out what happens when a call fails can be a challenge, but if you enable debug you can catch the exception and see all reads and writes to help debug the issue.

```php
$errMsg	= null;
try {

	$localHost			= \MTS\Factories::getDevices()->getLocalHost();
	$localHost->setDebug(true);

	$browserObj		= $localHost->getBrowser('phantomjs');
	
	$myUrl			= "https://www.wikipedia.org/";
	$windowObj		= $browserObj->getNewWindow($myUrl);

	//execute the trouble command here i.e:
	$funcReturn 	= $windowObj->callJSFunction("myHelloWorld");

	$browserObj->terminate();
	
} catch (\Exception $e) {
	switch($e->getCode()){
		default;
		$errMsg	= $e->getMessage();
	}
}

echo "Start Debug<<<\n <code><pre> \n ";
echo "Exception Message: " . $errMsg;
print_r($browserObj->getDebugData());
print_r($browserObj->getDebugFileContent());
echo "\n </pre></code> \n >>>End Debug";
```
