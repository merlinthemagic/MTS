This is a real Bash shell you can interact with through PHP, it does not have any of the limitations of the exec() or shell_exec() functions. The shell can be setup as root or as the user executing the php script. You control the terminal environtment and all variables are maintained throughout the session.

Basic use example:

  $shell		= \MTS\Factories::getDevices()->getLocalHost()->getShell('bash', true);
  
  $return1	= $shell->exeCmd('cd /var/log/');
  
  $return2	= $shell->exeCmd('ls -sho --color=none');
  
  echo $return2; // list files in '/var/log/'

Initial version works only on Centos 6 and 7.

Your server must have php 5.x installed and a webserver like apache. All other dependencies are resolved during setup.

Upload the MTS directory to a location on your server. i.e. /var/www/tools/. 
You cannot only upload the content of the directory, you must upload the directory and maintain the directory name (MTS).
Remember the location you uploaded to, you will need it later.

Place the 'MtsSetup.php' file in a folder that is published by your webserver.

Access the 'MtsSetup.php' file in a browser and follow the instructions. 
At the top of the page you will be asked to give 'Absolute Path to the directory that holds the MTS folder:'.
In this example that path is '/var/www/tools/', because inside the tools directory is the MTS directory you uploaded.

Once all dependencies have been resolved you will be provided a path that should be included in your
project whenever you wish to call a function included in the MTS kit.

Currently I have only ported the bash shell for CentOS but once i receive feedback on the package i can add further support.
