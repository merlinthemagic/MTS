### What is this?

Simple, its a tool set for PHP. Currently comprised of two core components, shell and browser.

This project strives to give developers the tools that let them automate processes that were designed for people.

My philosophy: <b>People should only do interesting work, computers can push the buttons. If it can be automated it should be.</b>

## The Shell:
The exec() or shell_exec() functions are good for executing single commands, but they are no where near as flexible as a real shell. Ever struggled to find out why a command returned nothing, hours later you find out its a permissions issue? 
Would it not be nice if the built in functions were more verbose. 

More generally the shell in Linux is very powerful, but PHP never had a good way to interact with it.
There are 100's of questions on sites like askubuntu.com or stackoverflow.com asking: my PHP script needs to run as root, how do I do it?
There has never been an easy way to accomplish that. That is by design, of course, letting PHP anywhere near root presents real security issues.

Regardless there are times when we need the power of root to get the job done. I fear some of us get lost in the sea of suggested solutions and opt for the easy solution: run apache as root. Everything equal that�s dangerous.
I hope this project will become the standard way to interact with the shell in PHP when the job requires root or real interaction with a shell. I want other programmers to comment and collaborate on this project in hopes we can mitigate as 
many of the security issues as possible.

The goal is to have easy shell access, allowing root when needed, as securely as possible.

### Basic use:

You start by following the installation instructions at the very bottom of this Readme, then instantiate a shell. The first argument on getShell() is the shell name (only bash for now). 
The second argument depends on weather you choose to allow sudo access to python during the installation.
If you choose to allow sudo on python then setting the second argument to true will return a shell logged in as root, while false will return a shell as the webserver user, i.e. apache or www-data.

```php
//Get a shell as the webserver user i.e. apache or www-data
$shellObj    = \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', false);

//Sudo enabled, get a shell as root
$shellObj    = \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', true);
```

The $shellObj variable now contains a bash shell object you can issue commands against. Here are a few examples:

```php
  $return1  = $shellObj->exeCmd('service sshd restart');
  echo $return1;
  
  //If you got a root shell redhat <7 distributions will return:
  //Stopping sshd:                                             [  OK  ]
  //Starting sshd:                                             [  OK  ]
```

```php
  $shellObj->exeCmd('cd /var/log/');
  $return1  = $shellObj->exeCmd('ls -sho --color=none');
  echo $return1; // list files in '/var/log/'
```

```php
  $return1  = $shellObj->exeCmd('whoami');
  echo $return1; // root or apache or www-data
```

Read the segment 'Using commands' below for more detail. 

### Root access:

Getting a shell with root privileges is easy and there are 2 ways to obtain it.

Like i mentioned above, if you allowed sudo to python during the installation then the second argument on getShell() is all you need. 
But for those users who are not comfortable with that type of setup, there is another option. 

```php
//Get a shell as the webserver user i.e. apache or www-data
$shellObj    = \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', false);

//Pass that shell to the following function with root credentials.
\MTS\Factories::getActions()->getRemoteUsers()->changeShellUser($shellObj, 'root', 'rootPassword');

$return1  = $shellObj->exeCmd('whoami');
echo $return1; //root
```


### How It Works:
>>>>>>> Replaced HTML elements with proper markdown syntax
We obtain a real shell by creating a Bash shell inside an instance of screen. 
You can then interact with it through PHP, you control the terminal environment and all variables are maintained throughout the session.

In terms of security, is it safe to allow sudo to python or pass root credentials in code?
The answer is of course not, but if you need root access it has inherent risk. The best solution is always to restructure your code so root is not needed, but now it is your choice.

### Remote Shells:

<h4>SSH:</h4>
You can also get a shell to a remote server through SSH if you like. Here is how:
```php
//Example
$shellObj = \MTS\Factories::getDevices()->getRemoteHost('ip_address')->getShellBySsh('username', 'password');
```

The returned shell can be used just like a local shell

### Using commands:
When you issue a command i.e. 'cat /etc/os-release' that command is executed in the shell and the output is returned to you.

The exeCmd() method takes 3 arguments.

string_command

delimitor

timeout

<h5>The string command:</h5>
Is the command you want to execute.

<h5>The delimitor:</h5>
Is a regular expression that is compared to the return data, once the expression is matched the data is returned.
By default the delimitor is set to a custom shell prompt determined by the shell class, so dont mess with the PS1 variable.

If you have a command that you want returned once the timeout expires you set it to false.
You would also change the delimitor if the command you are executing will not end in a prompt. One example could be you are doing a telnet session to another server.
in that case the delimitor should most likely be 'Password:', since doing a telnet login might prompt you for a password after the initial connect.

Note: If you plan on opening i.e. telnet connections or nesting a screen instance or changing shells, it will be your responsibillity to exit those sessions.
The process can only exit successfully if the shell contains the same bash shell as when it started. If you leave the shell inside i.e. screen then the process
may never terminate and would need to be terminated manually by you.


<h5>The timeout:</h5>
Is the absolute longest the current command is allowed to run. This argument is in mili seconds.
By default this is set to what remains of the 'max_execution_time'.

You would change this argument to 0 if you simply want to trigger the command and do not want any return.
If you have a command that runs for a long time and you just want the first 5 seconds of return then you set the delimitor to false and timeout to 5000.

Be careful with commands you timeout rather than delimit, you want to make sure they have completed before you issue the next one.
Here is an example:

$data  = $shellObj->exeCmd('ping www.google.com', false, 5000);

The standard ping command in bash will just keep going forever. You would get data back after 5 seconds, but the command is still running in the shell.
To stop the ping command above, you would have to call the 'killLastProcess()' method (the equivilent of hitting ^C), before issuing the next command or your next command will
never be executed and will timeout.

A much better command would be:

$data  = $shellObj->exeCmd('ping -c5 www.google.com');

This time the ping runs for 5 seconds and returns to the prompt.

Structure your commands the same way you would if you sat at the console and entered them manually. Its not magic, we are simply piping the terminal input output :)....

<h5>Debugging:</h5>

Figuring out what happens when a command fails can be a challenge, but if you enable debug you can catch the exception and see all reads and writes to help debug the issue.

```php
$errMsg	= null;
try {
	$localHost			= \MTS\Factories::getDevices()-&gtgetLocalHost();
	$localHost-&gtsetDebug(true);
	$shellObj			= $localHost-&gtgetShell('bash', false);
	
	//execute the trouble command here
	$data  = $shellObj-&gtexeCmd("command_that_fails_unexpectedly");
	$shellObj-&gtterminate();
	
} catch (\Exception $e) {
	switch($e-&gtgetCode()){
		default;
		$errMsg	= $e-&gtgetMessage();
	}
}
echo "Start Debug&gt&gt&gt\n &ltcode&gt&ltpre&gt \n ";
echo "Exception Message: " . $errMsg;
print_r($shellObj-&gtdebugData);
echo "\n &lt/pre&gt&lt/code&gt \n &lt&lt&ltEnd Debug";
```




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
You start by following the installation instructions at the very bottom of this Readme, then instantiate a browser.

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
echo '&ltimg src="data:image/jpeg;base64,' . base64_encode($screenshotData) . '" /&gt';
```

See "Window Methods" below for a complete list of examples

### How It Works:
PhantomJS is executed and the stdIn / stdOut are used to send and receive JSON encoded commands.
The JS file that is executed by PhantomJS is constantly checking its stdIn to see if any new commands have arrived.
Once a command is received the action is completed and the result returned.

You only have to worry about opening a page and manipulating it.

### Window Methods:

Set the size of the window:
```php
$width	= 640;
$height	= 480;
$windowObj->setSize($width, $height);
```

Set the area of the window you want to screenshot:
```php
$top	= 0;
$left	= 0;
$width	= 640;
$height	= 480;
$windowObj->setRasterSize($top, $left, $width, $height);
```

Take a screenshot of the window:
```php
$imageData	= $windowObj->screenshot();
```

Close window:
<pre>
$imageData	= $windowObj->close();
</pre>

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

Type with the keyboard:
```php
$keys	= "My Search Key Words";
$windowObj->sendKeyPresses($keys);

//sendKeyPresses takes a second argument, if you want to i.e. be holding "shift", "ctrl" or "alt" down while typing. 
$keys		= "My Search Key Words";
$modifiers	= array("shift").
$windowObj->sendKeyPresses($keys, modifiers);
```

Perform a mouse event on an element:
```php
$selector	= "[id=someElementId]";
$event		= "leftclick";
$windowObj->mouseEventOnElement($selector, $event);
```
use clickElement() rather than sending a mouseEventOnElement($selector, 'leftclick') when clicking hyperlinks.
mouseEventOnElement() can only click on elements that have 2D size, a hyperlink is just a line.

Click on an element:
```php
$selector	= "[id=someElementId]";
$windowObj->clickElement($selector);
```

Load some custom JavaScript in the window:
```php
$scriptData	= "function myHelloWorld() { return 'hello world' }";
$windowObj->loadJS($scriptData);
```

Call a JavaScript function:
```php
//only content that can be serialize by Json can be returned, no objects.
//$funcReturn will contain a string with the return from the function.
$funcReturn = $windowObj->callJSFunction("myHelloWorld");
```


Get details of an element i.e. value:
```php
//limited currently, will get more detail over time
$selector		= "[id=someElementId]";
$elementDetails	= $windowObj->getElement($selector);
```

Set the url in the window:
```php
$myUrl	= "http://www.google.com";
$windowObj->setURL($myUrl);
```

Set the User Agent:
<pre>
$agentName	= "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0";
$windowObj->setUserAgent($agentName);
</pre>

Set the scroll position in the window:
<pre>
//scroll down the page 500px
$top	= 500;
$left	= 0;
$windowObj->setScrollPosition($top, $left);
</pre>

Set if images should be loaded:
<pre>
//default is true, but setting to false will speed up loading by omitting images
$bool	= false;
$windowObj->setLoadImages($bool);
</pre>



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

<h5>Debugging:</h5>

Figuring out what happens when a call fails can be a challenge, but if you enable debug you can catch the exception and see all reads and writes to help debug the issue.

```php
$errMsg	= null;
try {

	$localHost			= \MTS\Factories::getDevices()-&gtgetLocalHost();
	$localHost-&gtsetDebug(true);

	$browserObj		= $localHost-&gtgetBrowser('phantomjs');
	
	$myUrl			= "https://www.wikipedia.org/";
	$windowObj		= $browserObj-&gtgetNewWindow($myUrl);

	//execute the trouble command here i.e:
	$funcReturn 	= $windowObj-&gtcallJSFunction("myHelloWorld");

	$browserObj-&gtterminate();
	
} catch (\Exception $e) {
	switch($e-&gtgetCode()){
		default;
		$errMsg	= $e-&gtgetMessage();
	}
}

echo "Start Debug&gt&gt&gt\n &ltcode&gt&ltpre&gt \n ";
echo "Exception Message: " . $errMsg;
print_r($browserObj-&gtdebugData);
echo "\n &lt/pre&gt&lt/code&gt \n &lt&lt&ltEnd Debug";
```

## Installation:

### Requirements:
Tested working against the following operating systems and versions.
```php
Centos 6, 7.
RedHat Enterprise 6.
Debian 8.
Ubuntu 16.
Arch 2016-05-01
```

It should work against other versions as long as they are the same flavor of Linux.

Mandetory packages:
```php
php5 (or newer)
php must allow the "exec()" function
python
screen
fontconfig
```

Optional packages:
```php
sudo
msttcore-fonts
```
If browser screenshots are not rendering text on buttons you are most likely missing the correct fonts. 


### Perform Install:
Upload the MTS directory to a location on your server. i.e. /var/www/tools/. 
You cannot only upload the content of the directory, you must upload the directory and maintain the directory name (MTS).
Remember the location you uploaded to, you will need it later.

You can run the setup in one of 2 ways:

1) Place the 'MtsSetup.php' file in a folder that is published by your webserver.
Then Access the 'MtsSetup.php' file in a browser and follow the instructions. 
At the top of the page you will be asked to give 'Absolute Path to the directory that holds the MTS folder:'.
In this example that path is '/var/www/tools/', because inside the tools directory is the MTS directory you uploaded.

2)
Run the setup from the command line of the server.
In this case you cannot move the 'MtsSetup.php' file, it must be located in the same directory as the 'MTS' directory.

Once all dependencies have been resolved you will be provided a path that should be included in your
project whenever you wish to call a function included in the MTS kit.
