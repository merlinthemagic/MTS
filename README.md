<h3>What is this?</h3>

The shell in Linux is very powerful, but PHP never had a good way to interact with it. This project wants to break down that barrier.
There are 100's of questions on sites like askubuntu.com or stackoverflow.com asking: my PHP script needs to run as root, how do I do it?
There has never been an easy way to accomplish that. That is by design, of course, letting PHP anywhere near root presents real security issues.

Regardless there are times when we need the power of root to get the job done. The fear I have is that some of us get lost in the sea of suggested solutions and opt for the easy solution: run apache as root. Everything equal that’s dangerous.
I hope this project will become the standard way to interact with the shell in PHP when the job requires root or real interaction with a shell. I want other programmers to comment and collaborate on this project in hopes we can mitigate as 
many of the security issues as possible.

The goal is to have easy shell access, allowing root when needed, as securely as possible.

<h3>Basic use:</h3>

This package creates a real Bash shell inside an instance of screen. You can then interact with it through PHP and it does not have any of the limitations of the exec() or shell_exec() functions. 
You control the terminal environment and all variables are maintained throughout the session.

You start by following the installation instructions below, then instantiate a shell. The first argument on getShell() is the shell name. 
The second argument depends on weather you choose to allow sudo access to python during the installation.
If you choose to allow sudo on python then setting the second argument to true will return a shell logged in as root, while false will return a shell as the webserver user, i.e. apache or www-data.

<pre>
//Get a shell as the webserver user i.e. apache or www-data
$shell    = \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', false);

//Sudo enabled, get a shell as root
$shell    = \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', true);
</pre>

The $shell variable now contains a bash shell object you can issue commands against. Here are a few examples:

<pre>
  $return1  = $shell->exeCmd('service sshd restart');
  echo $return1;
  
  //If you got a root shell redhat <7 distributions will return:
  //Stopping sshd:                                             [  OK  ]
  //Starting sshd:                                             [  OK  ]
</pre>

<pre>
  $shell->exeCmd('cd /var/log/');
  $return1  = $shell->exeCmd('ls -sho --color=none');
  echo $return1; // list files in '/var/log/'
</pre>

<pre>
  $return1  = $shell->exeCmd('whoami');
  echo $return1; // root or apache or www-data
</pre>

Read the segment 'Using commands' below for more detail. 

<h3>Root access:</h3>

Getting a shell with root privileges is easy and there are 2 ways to obtain it.

Like i mentioned above, if you allowed sudo to python during the installation then the second argument on getShell() is all you need. But for those users who are not comfortable with that type of setup, there is another option. 

<pre>
//Get a shell as the webserver user i.e. apache or www-data
$shell    = \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', false);

//Pass that shell to the following function with root credentials.
\MTS\Factories::getActions()->getRemoteUsers()->changeShellUser($shell, 'root', 'rootPassword');
</pre>

Is it safe to allow sudo to python or pass root credentials in code?
The answer is of course not, but if you need root access it has inherent risk. The best solution is always to restructure your code so root is not needed, but now you have the choice.


<h3>Requirements:</h3>
Tested working against the following operating systems and versions.
<pre>
Centos 6, 7.
RedHat Enterprise 6.
Debian 8.
Ubuntu 16.
</pre>

It should work against other versions as long as they are the same flavor of Linux.
PHP need only run as the standard webserver user, but the returned shell can have root priviliges.

Mandetory packages:
<pre>
php5 or newer, allowing exec().
python
screen
</pre>

Optional packages:
<pre>
sudo
</pre>


<h3>Installation</h3>

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

<h3>Using commands:</h3>
The project sets up a real bash shell inside a screen instance. When you issue a command i.e. 'cat /etc/os-release' that command is executed in the shell and the output is returned to you.

The exeCmd() method takes 3 arguments.

string_command

delimitor

timeout

<h5>The string command:</h5>
Is the command you want to execute.

<h5>The delimitor:</h5>
Is a regular expression that is compared to the return data, once the expression is matched the data is returned.
By default the delimitor is set to a custom shell prompt determined by the shell class. so dont mess with the PS1 variable.

If you have a command that you want returned once the timeout expires you set it to false.
You would also change the delimitor if the command you are executing will not end in a prompt. One example could be you are doing a ssh session to another server.
in that case the delimitor should most likely be 'Password:', since doing a ssh login will next prompt you for a password.

Note: If you plan on opening i.e. ssh connections or nesting a screen instance or changing shells, it will be your responsibillity to exit those sessions.
The process can only exit successfully if the shell contains the same bash shell as when it started. If you leave the shell inside i.e. screen then the process
never terminates and will need to be terminated manually, by you.  


<h5>The timeout:</h5>
Is the absolute longest the current command is allowed to run. This argument is in mili seconds.
By default this is set to 500 ms shorter than the 'max_execution_time'.

You would change this argument to 0 if you simply want to trigger the command and do not want any return.
If you have a command that runs for a long time and you just want the first 5 seconds of return then you set the delimitor to false and timeout to 5000.

Be careful with commands you timeout rather than delimit, you want to make sure they have completed before you issue the next one.
Here is an example:

$data  = $shell->exeCmd('ping www.google.com', false, 5000);

The standard ping command in bash will just keep going forever. You would get data back after 5 seconds, but the command is still running in the shell.
To stop the ping command above, you would have to call the 'killLastProcess()' method (the equivilent of hitting ^C), before issuing the next command or your next command will
never be executed and will timeout.

A much better command would be:

$data  = $shell->exeCmd('ping -c5 www.google.com');

This time the ping runs for 5 seconds and returns to the prompt.

Structure your commands the same way you would if you sat at the console and entered them manually. Its not magic, we are simply piping the terminal input output :)....