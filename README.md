This is a real Bash shell you can interact with through PHP, it does not have any of the limitations of the exec() or shell_exec() functions. You control the terminal environment and all variables are maintained throughout the session.

Tested working against the following operating systems and versions.
<pre>
Centos 6, 7.
RedHat Enterprise 6.
Debian 8.
Ubuntu 16.
</pre>

It should work against other versions as long as they are the same flavor of Linux.
PHP need only run as the standard webserver user, but the returned shell can have root priviliges.

There are 2 ways to obtain root priviliges.

1) Without sudo access to python you must first get a standard shell and then pass it through the following function to obtain root access:
<code>
//Bash shell as the webserver user i.e. apache or www-data
$shell    = \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', false);
\MTS\Factories::getActions()->getRemoteUsers()->changeShellUser($shell, 'root', 'rootPassword');
</code>

2) If the webserver user has sudo enabled for python then the second argument on getShell() determines if root is enabled. This may be a security risk.
The first argument is the shell name. The second argument is whether you want a shell with root priviliges
<code>
//Bash shell as root
$shell    = \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', true);
	    
//Bash shell as the webserver user i.e. apache or www-data
$shell    = \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', false); 
</code>

Basic use examples:

<code>
  $return1  = $shell->exeCmd('service sshd restart');
  echo $return1;
  
  //on redhat distributions that will return (if the shell was setup as root, as the webserver user would not have priviliges to services):
  //Stopping sshd:                                             [  OK  ]
  //Starting sshd:                                             [  OK  ]
</code>

<code>
  $shell->exeCmd('cd /var/log/');
  $return1  = $shell->exeCmd('ls -sho --color=none');
  echo $return1; // list files in '/var/log/'
</code>


<code>
  $return1  = $shell->exeCmd('whoami');
  echo $return1; // root or apache or www-data
</code>

Your server must have php 5 or newer installed and a webserver like apache. All other dependencies are resolved during setup.

Upload the MTS directory to a location on your server. i.e. /var/www/tools/. 
You cannot only upload the content of the directory, you must upload the directory and maintain the directory name (MTS).
Remember the location you uploaded to, you will need it later.

Place the 'MtsSetup.php' file in a folder that is published by your webserver.

Access the 'MtsSetup.php' file in a browser and follow the instructions. 
At the top of the page you will be asked to give 'Absolute Path to the directory that holds the MTS folder:'.
In this example that path is '/var/www/tools/', because inside the tools directory is the MTS directory you uploaded.

Once all dependencies have been resolved you will be provided a path that should be included in your
project whenever you wish to call a function included in the MTS kit.