# Installation:

### Requirements:
Tested working against the following operating systems and versions.
```php
Centos 6, 7.
Windows 7 (Still Experimental).
RedHat Enterprise 6.
Debian 8.
Ubuntu 16.
Arch 2016-05-01

```

It should work against other Linux versions as long as they are the same flavor.

####Packages Linux:
```php
Mandetory:

php 5.3 (or newer)
php must allow the "exec()" function
python
screen
fontconfig

Optional:
sudo
msttcore-fonts
```

####Packages Windows:
```php
Mandetory:

php 5.3 (or newer)
php must allow the "exec()" function
php must allow the "popen()" function
php must allow the "pclose()" function
php must allow the "proc_open()" function

```

If browser screenshots are not rendering text on buttons you are most likely missing the correct fonts. 


### Perform Install:

#### Linux:
You can run the setup in one of 3 ways:

1) Composer Install.
This assumes you have composer installed already.
Issue command "composer require merlinthemagic/mts" to make it part of your requirements
After install you will need to execute the "MtsSetup.php" file in the root of the package,
and follow the last installation steps (see option 2 or 3 for how to complete this step). 
This because Composer will not trigger the "post-install-cmd" of a dependency.

2-3) Manual Install:
Download MTS from GitHub and upload the MTS directory to a location on your server. i.e. /var/www/tools/. 
You cannot only upload the content of the directory, you must upload the directory and maintain the directory name (MTS).
Remember the location you uploaded to, you will need it later.
Then complete install with option 2 OR 3 below.

2) Place the 'MtsSetup.php' file in a folder that is published by your webserver.
Then Access the 'MtsSetup.php' file in a browser and follow the instructions. 
At the top of the page you will be asked to give 'Absolute Path to the directory that holds the MTS folder:'.
In this example that path is '/var/www/tools/', because inside the tools directory is the MTS directory you uploaded.

3)
Run the setup from the command line of the server.
In this case you cannot move the 'MtsSetup.php' file, it must be located in the same directory as the 'MTS' directory.

Once all dependencies have been resolved you will be provided a path that should be included in your
project whenever you wish to call a function included in the MTS kit.

#### Windows:
You can run the setup in one of 2 ways:

1) Composer Install.
This assumes you have composer installed already.
Issue command "composer require merlinthemagic/mts" (dev version) to make it part of your requirements

2) Manual Install:
Download MTS from GitHub and upload the MTS directory to a location on your server. i.e. C:\inet\wwwroot\tools\MTS\. 
You cannot only upload the content of the directory, you must upload the directory and maintain the directory name (MTS).

Whenever you wish to use the MTS tools add:
require_once "c:\path\to\mts\folder\EnableMTS.php";

(replace c:\path\to\mts\folder with whatever path you chose to place the package in i.e. C:\inet\wwwroot\tools\MTS)
