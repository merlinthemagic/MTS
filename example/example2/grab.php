<?php
set_time_limit(5*60); //we assume all this process done under 5 min
require_once "../../MTS/EnableMTS.php";
$url = "http://localhost/MTSPath/mts/example/example2/main.html";
$windowObj = \MTS\Factories::getDevices()->getLocalHost()->getBrowser('phantomjs')->getNewWindow($url); 

echo 'static-content: '. $windowObj->getElement('.static-content')['innerHTML'].PHP_EOL;

//left click on the button element 
$windowObj->clickElement("button");
sleep(2); //wait ajax done
echo 'new-content-ajax: '. $windowObj->getElement('.new-content-ajax')['innerHTML'].PHP_EOL;

sleep(1); //wait 3 (2+1) sec until new element created
echo 'new-content-createElement: '. $windowObj->getElement('.new-content-createElement > h1')['innerHTML'].PHP_EOL;

/*output:
static-content: magician
new-content-ajax: i'm merlin
new-content-createElement: Welcome to my website
*/
?>