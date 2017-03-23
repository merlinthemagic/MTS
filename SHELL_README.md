
## The Shell:
The exec() or shell_exec() functions are good for executing single commands, but they are no where near as flexible as a real shell. Ever struggled to find out why a command returned nothing, hours later you find out its a permissions issue? 
Would it not be nice if the built in functions were more verbose?

More generally the shell in Linux is very powerful (and Powershell is a definite improvement in Windows), but PHP never had a good way to interact with it.
There are 100's of questions on sites like askubuntu.com or stackoverflow.com asking: my PHP script needs to run as root, how do I do it?
There has never been an easy way to accomplish that. That is by design, of course, letting PHP anywhere near root presents real security issues.

Regardless there are times when we need the power of root to get the job done. I fear some of us get lost in the sea of suggested solutions and opt for the easy solution: run apache as root. Everything equal that’s dangerous.
I hope this project will become the standard way to interact with the shell in PHP when the job requires root or real interaction with a shell. I want other programmers to comment and collaborate on this project in hopes we can mitigate as 
many of the security issues as possible.

The goal is to have easy shell access, allowing root when needed, as securely as possible.

### Basic use:

You start by following the installation instructions at the very top of this Readme, then instantiate a shell. The first argument on getShell() is the shell name (for now the options are "powershell", "cmd", "bash"). 
The second argument depends on weather you choose to allow sudo access to python during the installation.
If you choose to allow sudo on python (Linux Only) then setting the second argument to true will return a shell logged in as root, while false will return a shell as the webserver user, i.e. apache or www-data.

```php
//Get a shell as the webserver user i.e. apache or www-data
$shellObj    = \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', false);

//Sudo enabled, get a shell as root
$shellObj    = \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', true);

//For windows you can specify powershell or cmd
$shellObj    = \MTS\Factories::getDevices()->getLocalHost()->getShell('powershell');
```

The $shellObj variable now contains a bash shell object you can issue commands against. Here are a few examples:

```php
  $return1  = $shellObj->exeCmd('service sshd restart');
  echo $return1;
  
  //If you got a root shell redhat <7 distributions will return:
  //Stopping sshd:                                             [  OK  ]
  //Starting sshd:                                             [  OK  ]
  
  //or on windows, list of all processes
  $return1  = $shellObj->exeCmd('Get-Process');
  echo $return1;
```

```php
  //Linux
  $shellObj->exeCmd('cd /var/log/');
  $return1  = $shellObj->exeCmd('ls -sho --color=none');
  echo $return1; // list files in '/var/log/'
  
  //Windows
  $shellObj->exeCmd("cd c:\\");
  $return1  = $shellObj->exeCmd("dir");
  echo $return1; // list files in 'c:\'
```

```php
  $return1  = $shellObj->exeCmd('whoami');
  echo $return1; // root, apache, www-data, http, "nt authority\iusr" etc...
```

Read the segment 'Using commands' below for more detail. 

### Root access (Linux Only):

Getting a shell with root privileges is easy and there are 2 ways to obtain it.

Like i mentioned above, if you allowed sudo to python during the installation then the second argument on getShell() is all you need. 
But for those users who are not comfortable with that type of setup, there is another option. 

```php
//Get a shell as the webserver user i.e. apache or www-data
$shellObj		= \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', false);

//Pass the shellObj to the following function with root credentials.
\MTS\Factories::getActions()->getRemoteUsers()->changeUser($shellObj, 'root', 'rootPassword');

$return1 	= $shellObj->exeCmd('whoami');
echo $return1; //root
```

### How It Works:
PHP need only run as the standard webserver user, but the returned shell can have root priviliges.

We obtain a real shell by creating a Bash shell inside an instance of screen.
You can then interact with the shell through PHP by calling exeCmd() on the shell object. 
You control the terminal environment and all variables, except for $PS1 (used as standard delimitor). 
All variables are maintained throughout the session. The Python setup provides the shell with a PTY (pseudo-teletype), this allows you to run applications that require TTY.

In terms of security, is it safe to allow sudo to python or pass root credentials in code?
The answer is of course not, but if you need root access it has inherent risk. The best solution is always to restructure your code so root is not needed, but now it is your choice.

### Remote Shells (Linux Only):

#### SSH:
You can also get a shell to a remote server through SSH if you like. Here is how:
```php
//Example
$shellObj		= \MTS\Factories::getDevices()->getRemoteHost('ip_address')->setConnectionDetail('username', 'password')->getShell();
```

The returned shell can be used just like a local shell.

You also have the option to keep building on a remote SSH shell like this:
 
```php
//Server 1 shell:
$shellObj		= \MTS\Factories::getDevices()->getRemoteHost('ip_address1')->setConnectionDetail('username1', 'password1')->getShell();

//Server 2 shell. Use the shell from the first server to login to a second server
\MTS\Factories::getDevices()->getRemoteHost('ip_address2')->setConnectionDetail('username2', 'password2')->getShell($shellObj);

//any commands executed on $shellObj will be executed on Server 2.

```

### Using commands:
When you issue a command i.e. 'cat /etc/os-release' that command is executed in the shell and the output is returned to you.

The exeCmd() method takes 3 arguments.

string_command

delimitor

timeout

##### The string command:
Is the command you want to execute.
If you set this to false then no command will be executed and we will just read pending data until the delimitor is reached (if specified).

##### The delimitor:
Is a regular expression that is compared to the return data, once the expression is matched the data is returned.
By default the delimitor is set to a custom shell prompt determined by the shell class, so dont mess with the PS1 variable.

If you have a command that you want returned once the timeout expires you set it to false.
You would also change the delimitor if the command you are executing will not end in a prompt. One example could be you are doing a telnet session to another server.
in that case the delimitor should most likely be 'Password:', since doing a telnet login might prompt you for a password after the initial connect.

Note: If you plan on opening i.e. telnet connections or nesting a screen instance or changing shells, it will be your responsibillity to exit those sessions.
The process can only exit successfully if the shell contains the same bash shell as when it started. If you leave the shell inside i.e. screen then the process
may never terminate and would need to be terminated manually by you.


##### The timeout:
Is the absolute longest the current command is allowed to run. This argument is in mili seconds.
By default this is set to 10000 ms. The default value can be changed by calling $shellObj->setDefaultExecutionTime($miliSecs).

You would change this argument to "false" if you simply want to trigger the command and do not want any return.
If you have a command that runs for a long time and you just want the first 5 seconds of return then you set the delimitor to false and timeout to 5000.

Be careful with commands you timeout rather than delimit, you want to make sure they have completed before you issue the next one.
Here is an example:

```php
$data  = $shellObj->exeCmd('ping www.google.com', false, 5000);
```

The standard ping command in bash will just keep going forever. You would get data back after 5 seconds, but the command is still running in the shell.
To stop the ping command above, you would have to call the 'killLastProcess()' method (the equivilent of hitting ^C), before issuing the next command or your next command will
never be executed and will timeout.

A much better command would be:

```php
$data  = $shellObj->exeCmd('ping -c5 www.google.com');
```

This time the ping runs for 5 seconds and returns to the prompt.

Structure your commands the same way you would if you sat at the console and entered them manually. Its not magic, we are simply piping the terminal input output :)....

##### Debugging:

Figuring out what happens when a command fails can be a challenge, but if you enable debug you can catch the exception and see all reads and writes to help debug the issue.

```php
$errMsg	= null;
try {
	$localHost			= \MTS\Factories::getDevices()->getLocalHost();
	$localHost->setDebug(true);
	$shellObj			= $localHost->getShell('bash', false);
	
	//execute the trouble command here
	$data  = $shellObj->exeCmd("command_that_fails_unexpectedly");
	$shellObj->terminate();
	
} catch (\Exception $e) {
	switch($e->getCode()){
		default;
		$errMsg	= $e->getMessage();
	}
}
echo "Start Debug<<<\n <code><pre> \n ";
echo "Exception Message: " . $errMsg;
print_r($shellObj->getDebugData());
echo "\n </pre></code> \n >>>End Debug";
```