My philosophy: 

<b>People should only do interesting work. If it can be automated it should be automated.</b>

## What is MTS?

MTS is a PHP library used to handle shells and browsers. Which is hard for PHP to handle both of them using built-in function. MTS can give you power to automate processes both of them (browser and shell).

There are two component in MTS
* <a href="https://github.com/plonknimbuzz/MTS/blob/master/README.md#the-browser">The Browser</a>
* <a href="https://github.com/plonknimbuzz/MTS/blob/master/README.md#the-shell">The Shell</a>

## The Browser:
With this component you can handle browser behaviour using PHP. To do this, MTS used PhantomJS as a headless browser. Using this component, you can do this:
- take webpage screenshot 
- run javascript or add new javascript in the webpage 
- get DOM HTML element
- manipulate the webpage
- trigger event on webpage (click, enter, type word, focus, etc)
- automate something
- many more ...

Take a look for the example: (*you can found this in example folder*)
- <a href="https://github.com/plonknimbuzz/MTS/blob/master/README.md#example-1-input-something-in-inputtext">1. Input something in input=text</a>
- <a href="https://github.com/plonknimbuzz/MTS/blob/master/README.md#example-1-input-something-in-inputtext">2: Scrapping a content of website</a>

### Example 1: Input something in input=text
Most of web developer sometimes meet hard practice or create duplicate function which one run in browser and 1 run in CLI. This example will explain how to solve this case easly using MTS.

First, We create demo. In this case we have script that will display name which user inserted in input text and save it into database (we simulate to write on text file instead insert to db).

input.html
```
<form method="post" action="process.php">
	Your Name: <input type="text" name="name" value="">
	<input type="submit" name="submit" value="save date">
</form>
```
process.php
```
<?php  
	if(isset($_POST['name']) $name = $_POST['name'];
	else die('no name');
	echo $name; 
	file_put_content('file/'. $name.".txt", $name);
?>
```

when we run this in CLI, we need create another script that send request post using curl. Or we can modify process.php to accept PHP arguments, like this
```
<?php  if(isset($_POST['name'])) $name = $_POST['name'];
	elseif(isset($args[1])) $name= $args[1];
	else die('no name');
	echo $name; 
	file_put_contents('file/'. $name .".txt", $name);
?>
```
so we can execute with `php process.php "Charles Darwin"` 

we can easly modify this script, because this is only SIMPLE script. How about if our application is already bigger/complex OR we cant modify the source, because we dont have privilidge to do that. Then we want automate the process. Let's say you have list_people.txt 
```
NameA
NameB
NameC
NameD
....
NameZ
```

Which need to inserted in our apps. Ofcourse we avoid to insert it manually. We have a lot of alternative to do this: phantomJS, casperJS, slimmerJS, selenium, etc but most of them write using javascript. if we want to do this using PHP, let's do this with MTS.

NOTE: this script will looks longer, because we want to explain about the detail.
batch_input.php
```
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
```
After we execute that script. we will get bunch of personName.txt in our folder

*What we did?*
We simulate browser to open the page -> focus on input text -> type something -> press enter

*conclusion from example 1 :*
- if our apps is not complex or not yet finish developed, and we can modify the source, we recommend to edit your source code / create another script to automate that.
- if our apps is too complex or already finished we recommend use MTS which is no need to broke your old code.
- if you dont have access to modify the scriptm like searching article in wikipedia/google, login at fb/twitter, post something in forum. if that website have API and you ready to struggle with that, we recommend to learn the API. but if they dont have API or you want take the easy part, you can use MTS for this.

### example 2: Scrapping a content of website
if you want to scrap/grab content of website you will meet 2 condition of this:
1. content that can get via server side scripting
in this case we PHP user commonly use: file_get_contents, curl, regular expression, or php library for parsing html dom like simple_dom_parser or advanced_dom_parser
2. content that can get only from client side scripting
*some website hide their content using javascript like AJAX, new element(dom) creation, encryption, flash, or hide with https, captcha, or some trick that prevent bot (automate program/application) to steal their content. Of course in this case, we cant get it using PHP, we must get this using several tools like greasemonkey, macro adds-on, etc.
*for example, surely you know about adf.ly. They are url shortener server which will pay you if someone click your link. In this bussiness, of course they wont allow bot to click their links. So they will create very complex encryption to do this. If you want bypass the ads, you can decrypt the encryption which is hard or just get someone script who can decrypt it from internet. But ofc they are not stup*d, they will change the encryption in unspecific times. So we call this (almost) impossible for server scripting to grab the real link.

But we can still get it by server side scripting using phantomJS which is using javascript. So if you are PHP developer and you want to use them, you need split your script 1 for the javascript and 1 for PHP. But this will not happen if you use MTS. MTS use phantomJS in the browser component, so MTS can do all the phantomJS can do. with MTS:
- You dont need struggle to learn javascript programming and phantomJS docs
- you dont need to split your code which is sometimes hard to handle phantomJS script return with PHP
- AND.. all your code will write on pure PHP

Hei, what's about adf.ly??
no, we wont ruin someone bussiness and we wont use live website as example, because we always need to modify script example if the real website change their html (prevent dependencies). But you can do this easly using MTS, just find the way by yourself.

So, let's create the example
NOTE: you can get this example2 code on example folder

main.html
```
<div class="static-content">magician</div>
<div>Who am i? <b class="new-content-ajax"></b></div>
<button>press me for the answer</button>
<div class="new-content-createElement"></div>
<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script>
	$(function(){
		$('button').click(function(){
			$.ajax({
				url: 'process.php',
				type: 'post',
				success: function(d){
					$('.new-content-ajax').text(d);
				},
				error: function(){
					alert('error');
				}
			})
		});
		
		setTimeout(function(){
			$('.new-content-createElement').html('<div>trash</div>');
			$('.new-content-createElement').append('<div>trash</div>');
			$('.new-content-createElement').append('<h1>Welcome to my website</h1>');
			$('.new-content-createElement').append('<div>trash</div>');
			$('.new-content-createElement').append('<div>trash</div>');
		}, 5000);
	});
</script>
```
process.php
```
<?php echo "i'm merlin"; ?>
```

to grab `.static-content` we can use simple script PHP like this:
```
<?php 
	$html = file_get_contents("http://localhost/MTSpath/example/example2/main.html");
	preg_match('/<div class="static-content">(.*?)<\/div>/is', $html, $match);
	echo $match[0]; //output: magician
?>
```
But we never get `.new-content-ajax` or `.new-content-createElement`, because DOM/content isnt already there when PHP download the main.html. In this case we can use MTS.

```
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
?>
```
this will output:
```
static-content: magician
new-content-ajax: i'm merlin
new-content-createElement: Welcome to my website
```
Awesome right? :+1:

To learn how to use MTS browser component, please read the docs: <a href="https://github.com/merlinthemagic/MTS/blob/master/BROWSER_README.md">Browser Documentation</a>.

## The Shell:
<a href="https://github.com/merlinthemagic/MTS/blob/master/SHELL_README.md">Shell Documentation</a>.

## The Install:
<a href="https://github.com/merlinthemagic/MTS/blob/master/INSTALL.md">Installation Documentation</a>.
