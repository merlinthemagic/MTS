<h3>What is this project?</h3>

This is a real Bash shell you can interact with through PHP, it does not have any of the limitations of the exec() or shell_exec() functions. You control the terminal environment and all variables are maintained throughout the session.


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
php5 or newer
python
screen
</pre>

Optional packages:
<pre>
sudo
</pre>

<h3>Obtaining root access:</h3>
There are 2 ways to obtain root priviliges.

1) Without sudo access to python you must first get a standard shell and then pass it through the following function to obtain root access:
<pre>
//Bash shell as the webserver user i.e. apache or www-data
$shell    = \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', false);
\MTS\Factories::getActions()->getRemoteUsers()->changeShellUser($shell, 'root', 'rootPassword');
</pre>

2) If the webserver user has sudo enabled for python then the second argument on getShell() determines if root is enabled. This may be a security risk.
The first argument is the shell name. The second argument is whether you want a shell with root priviliges
<pre>
//Bash shell as root
$shell    = \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', true);

//Bash shell as the webserver user i.e. apache or www-data
$shell    = \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', false); 
</pre>

Basic use examples:

<pre>
  $return1  = $shell->exeCmd('service sshd restart');
  echo $return1;
  
  //on redhat distributions that will return (if the shell was setup as root, as the webserver user would not have priviliges to services):
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
in that case the delimitor should most likely be 'Passsword:', since doing a ssh login will next prompt you for a password.

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