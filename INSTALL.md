## Operating System
Tested working against the following operating systems and versions.
- Windows 7 32bit, 7 64bit *(Still Experimental)*
- Centos 6, 7
- RedHat Enterprise 6
- Debian 8
- Ubuntu 14, 16
- Arch 2016-05-01

It should work against other Linux versions as long as they are the same flavor.

## Requirements:
### Linux:
- php 5.3 above and must allow these function:
	- exec()
	- popen()
	- proc_open()
- python
- screen
- fontconfig
- optional:
	- sudo
	- msttcore-fonts 

### Windows:
- php 5.3 above and must allow these function:
	- exec()
	- popen()
	- proc_open()
- python (make sure python registered in SYSTEM PATH ENVIRONMENT)

> If browser screenshots are not rendering text on buttons you are most likely missing the correct fonts. 


## Installation:


### Windows:
1. Upload MTS to your directory.
	- you can use `composer require merlinthemagic/mts`
	- or just download the repository (zip)
2. include `EnableMTS.php` in your script. 
	- example: `require_once "../MTSPath/MTS/EnableMTS.php";`

### Linux:
You can run the setup in several ways:

1. Download MTS
	1. Composer Install.
		- run `composer require merlinthemagic/mts`
		- After install you will need to execute the "MtsSetup.php" file in the root of the package, and follow the last installation steps (see installation option 2.1 or 2.2 for how to complete this step). # __*This because Composer will not trigger the "post-install-cmd" of a dependency.*__

	2. Manual Install:
	- Download MTS from GitHub and upload the MTS directory to a location on your server. i.e. `/var/www/tools/`. 
	- You cannot only upload the content of the directory, you must upload the directory and maintain the directory name (MTS).
	- Remember the location you uploaded to, you will need it later.
	- Then complete install with option 2.1 OR 2.2 below.

2. Install MTS (2 ways)
	1. First way
		- Place the `MtsSetup.php` file in a folder that is published by your webserver.
		- Then Access the `MtsSetup.php` file in a browser and follow the instructions. 
		- At the top of the page you will be asked to give 'Absolute Path to the directory that holds the MTS folder:'. In this example that path is `/var/www/tools/`, because inside the tools directory is the MTS directory you uploaded.

	2. Second way
		- Run the setup from the command line of the server.
		- In this case you cannot move the `MtsSetup.php` file, it must be located in the same directory as the 'MTS' directory.

> Once all dependencies have been resolved you will be provided a path that should be included in your project whenever you wish to call a function included in the MTS kit.


## Test the script
```php
<?php
  set_time_limit(120);
  require_once "../../MTS/EnableMTS.php"; //navigate to MTS directory
  $windowObj = \MTS\Factories::getDevices()->getLocalHost()->getBrowser('phantomjs')->getNewWindow("https://github.com");
  echo $windowObj->getElement('title')['text']; //output: Wikipedia
?>
```
run this from browser or CLI. And when you got the correct output `Wikipedia`, your MTS will ready to use.
