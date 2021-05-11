<?php
set_time_limit(5*60); //we assume all this process done under 5 min
require_once "../../MTS/EnableMTS.php";
$url = "http://localhost/MTSpath/example/example1/input.html";
$windowObj = \MTS\Factories::getDevices()->getLocalHost()->getBrowser('phantomjs')->getNewWindow(); //MTS command phantomjs to open new browser 
$selector = 'input[type="text"]'; //CSS3 selector to get input text
$txt = file_get_contents('list_people.txt'); //get the list people txt
$exp = explode("\n", $txt); //explode it using new line as delimiter
foreach($exp as $person){
	$windowObj->setURL($url); //open the url
	$windowObj->focusElement($selector); //focus to the input text element , like $(selector).focus() in jquery
	$windowObj->sendKeyPresses($person); //type each name to the input text
	$windowObj->sendKeyPresses(array("Enter")); //press Enter to trigger form submitted
	sleep(5); //wait until input successfully submitted. This will depends on your internet speed and target server speed processing
}
?>
