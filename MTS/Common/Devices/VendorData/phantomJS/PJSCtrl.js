//requirements
var system 					= require('system');
var fSystem					= require('fs');
var webpage					= require('webpage');
var initArgs				= system.args;

var terminationEpoch		= (getEpoch(false) + 10);
var classData				= {};
classData.workPath			= initArgs[1];
classData.stdIn				= {};
classData.stdIn.path		= classData.workPath + "stdIn";

classData.cmdStack			= [];
classData.initialized		= false;
classData.debug				= false;
classData.debugPath			= null;
classData.loadWaitInterval	= 500;
classData.windows			= [];

//make sure auto termination is running
forceTermination();
//start the process
processLoop();

function processLoop()
{
	try {

		//get the next command
		var cmdObj	= getCommand();
		if (cmdObj !== false) {
			//execute the command
			exeCmd(cmdObj);
		} else {
			//no pending commands, wait a bit
			setTimeout(function() {
				processLoop();
			}, 50);
		}

	} catch(e) {
		var eMsg	= "Failed process loop";
		writeError(e, eMsg);
	}
}
function exeCmd(cmdObj)
{
	try {
	
		if (classData.debug === true) {
			writeDebug(arguments.callee.name, "Executing Cmd: " + cmdObj.cmd.name);
		}
		
		if (classData.initialized === true) {

			if (cmdObj.cmd.name == "terminate") {
				//never wait for a page on termination
				terminate(cmdObj);
			} else if (cmdObj.cmd.name == "seturl") {
				commandWaitForWindowLoad(cmdObj, setUrl);
			} else if (cmdObj.cmd.name == "geturl") {
				commandWaitForWindowLoad(cmdObj, getUrl);
			} else if (cmdObj.cmd.name == "getdom") {
				commandWaitForWindowLoad(cmdObj, getDom);
			} else if (cmdObj.cmd.name == "loadjs") {
				commandWaitForWindowLoad(cmdObj, loadJS);
			} else if (cmdObj.cmd.name == "jscallfunction") {
				commandWaitForWindowLoad(cmdObj, JSCallFunction);
			} else if (cmdObj.cmd.name == "screenshot") {
				commandWaitForWindowLoad(cmdObj, screenshot);
			} else if (cmdObj.cmd.name == "focuselement") {
				commandWaitForWindowLoad(cmdObj, focusElement);
			} else if (cmdObj.cmd.name == "getelement") {
				commandWaitForWindowLoad(cmdObj, getElement);
			} else if (cmdObj.cmd.name == "getdocument") {
				commandWaitForWindowLoad(cmdObj, getDocument);
			} else if (cmdObj.cmd.name == "sendkeypresses") {
				commandWaitForWindowLoad(cmdObj, sendKeyPresses);
			} else if (cmdObj.cmd.name == "clickelement") {
				commandWaitForWindowLoad(cmdObj, clickElement);
			} else if (cmdObj.cmd.name == "getcookies") {
				commandWaitForWindowLoad(cmdObj, getCookies);
			} else if (cmdObj.cmd.name == "setcookie") {
				commandWaitForWindowLoad(cmdObj, setCookie);
			} else if (cmdObj.cmd.name == "mouseeventonelement") {
				commandWaitForWindowLoad(cmdObj, mouseEventOnElement);
			} else if (cmdObj.cmd.name == "getselectorexists") {
				commandWaitForWindowLoad(cmdObj, getSelectorExists);
			} else if (cmdObj.cmd.name == "closewindow") {
				//no need to wait for load
				closeWindow(cmdObj);
			} else if (cmdObj.cmd.name == "setdebug") {
				//no need to wait for load
				setDebug(cmdObj);
			} else {
				var eMsg	= "Unknown command: " + cmdObj.cmd.name;
				writeError(null, eMsg);
			}

		} else if (cmdObj.cmd.name == "initialize") {
			initialize(cmdObj);
		} else {
			//first command must be init
			throw "PhantomJS is not initialized. Cannot execute command: " + cmdObj.cmd.name;
		}
		
		//make sure the command will return at timeout
		setTimeout(function() {
			if (cmdObj.result.returned === false) {
				cmdObj.result.error.msg		= "timeout";
				cmdObj.result.error.code	= 408;
				writeReturn(cmdObj);
			}
		}, cmdObj.cmd.timeout);

	} catch(e) {
		var eMsg	= "Command execution exception not handled";
		writeError(e, eMsg);
		cmdObj.result.error.msg		= e;
		writeReturn(cmdObj);
		//make sure every function handles exception, or we will get multiple or they end here. 
		//We cannot return to the processLoop, we risk multiple processes at the same time = havoc
		terminate(null);
	}
}


//functions
function setUrl(cmdObj)
{
	try {
		
		//validate
		var windowObj	= getWindowByCommand(cmdObj);
		if (typeof cmdObj.cmd.options.url == 'undefined') {
			throw "URL must be set";
		}

		windowObj.pjsPage.open(cmdObj.cmd.options.url, function(status) {
			if (status == 'success') {
				cmdObj.result.code		= 200;
				writeReturn(cmdObj);
				processLoop();
			} else {
				cmdObj.result.error.msg		= "Failed with status: " + status;
				writeReturn(cmdObj);
				processLoop();
			}
		});

	} catch(e) {
		cmdObj.result.error.msg		= "Failed to set Url. Error: " + e;
		writeReturn(cmdObj);
		processLoop();
	}
}
function getUrl(cmdObj)
{
	try {
		
		//validate
		var windowObj				= getWindowByCommand(cmdObj);
		
		cmdObj.result.data.script	= windowObj.pjsPage.url;
		cmdObj.result.code			= 200;
		writeReturn(cmdObj);
		processLoop();

	} catch(e) {
		cmdObj.result.error.msg		= "Failed to get Url. Error: " + e;
		writeReturn(cmdObj);
		processLoop();
	}
}
function closeWindow(cmdObj)
{
	try {
		
		var windowObj	= getWindowByCommand(cmdObj);
		var winLen		= classData.windows.length;
		if (winLen > 0) {
			
			for (var i=0; i < winLen; i++) {
				var winObj	= classData.windows[i];
				if (windowObj.uuid == winObj.uuid) {
					//found the correct window
					//close the window and free up the memory
					windowObj.pjsPage.close();
					//remove from the array
					classData.windows.splice(i, 1);
					break;
				}
			}
		}

		cmdObj.result.code	= 200;
		
		writeReturn(cmdObj);
		processLoop();

	} catch(e) {
		cmdObj.result.error.msg		= "Failed to close window. Error: " + e;
		writeReturn(cmdObj);
		processLoop();
	}
}


function getSelectorExists(cmdObj)
{
	try {

		//validate
		var windowObj	= getWindowByCommand(cmdObj);
		if (typeof cmdObj.cmd.options.selector == 'undefined') {
			throw "Selector must be set";
		}

		var selector	= cmdObj.cmd.options.selector;
		var result		= windowObj.pjsPage.evaluate(function(selector){
	        var element = document.querySelector(selector);
	        
	        if (element === null) {
	        	return 'selectorNotExist';
	        } else {
	        	return 'selectorExist';
	        }
	       
	    }, selector);
		
		if (result == 'selectorExist') {
			cmdObj.result.code			= 200;
			cmdObj.result.data.dom		= 1;
		} else if (result == 'selectorNotExist') {
			cmdObj.result.code			= 200;
			cmdObj.result.data.dom		= 0;
		}  else {
			throw "Invalid Return: " + result;
		}
		writeReturn(cmdObj);
		processLoop();

	} catch(e) {
		cmdObj.result.error.msg		= "Failed to get selector exists. Error: " + e;
		writeReturn(cmdObj);
		processLoop();
	}
}

function getElement(cmdObj)
{
	try {

		//validate
		var windowObj	= getWindowByCommand(cmdObj);
		if (typeof cmdObj.cmd.options.selector == 'undefined') {
			throw "Selector must be set";
		}

			var selector	= cmdObj.cmd.options.selector;
			var result		= windowObj.pjsPage.evaluate(function(selector){
	        var element		= document.querySelector(selector);
	        
	        if (element === null) {
	        	 return 'selectorNotExist';
	        } else {
	        	
	        	//keep adding data on element over time, this shoud be the primary way to get data on
	        	//an element outside of getDom()
	        	
	        	try {
		        	
		        	var result			= {};
		        	result.tagName		= element.tagName;
		        	
		        	//get value
		        	if (typeof element.value !== "undefined") {
		        		result.value	= element.value;
		    		} else {
		    			result.value	= null;
		    		}
		        	//get type
		        	if (typeof element.type !== "undefined") {
		        		result.type		= element.type;
		    		} else {
		    			result.type		= null;
		    		}
		        	//get text
		        	if (typeof element.text !== "undefined") {
		        		result.text		= element.text;
		    		} else {
		    			result.text		= null;
		    		}
		        	
		        	//get innerHTML
		        	if (typeof element.innerHTML !== "undefined") {
		        		result.innerHTML	= encodeURIComponent(element.innerHTML);
		    		} else {
		    			result.innerHTML	= null;
		    		}
		        	
		        	//get location
		        	result.location				= {};
		        	var rect 					= element.getBoundingClientRect();
		        	result.location.top			= rect.top;
		        	result.location.bottom		= rect.bottom;
		        	result.location.right		= rect.right;
		        	result.location.left		= rect.left;
    	
		        	return JSON.stringify(result);
		        	
	        	} catch(e) {
	        		return "Get Element Inside Evaluate. Error: " + String(e);
	        	}
	        }
	       
	    }, selector);
		
		if (result == 'selectorNotExist') {
			cmdObj.result.error.msg		= selector + " does not exist";
		} else if (result.match(/^Get Element Inside Evaluate/)) {
			cmdObj.result.error.msg		= result;
		} else {
			cmdObj.result.code			= 200;
			cmdObj.result.data.dom		= result;
		}
		writeReturn(cmdObj);
		processLoop();

	} catch(e) {
		cmdObj.result.error.msg		= "Failed to get element. Error: " + e;
		writeReturn(cmdObj);
		processLoop();
	}
}
function getDocument(cmdObj)
{
	try {

		//validate
		var windowObj	= getWindowByCommand(cmdObj);
		var result		= windowObj.pjsPage.evaluate(function(){

        	//keep adding data on document over time, this shoud be the primary way to get data on
        	//the document outside of getDom()
	        	
        	try {
	        	
	        	var result									= {};
	        	
	        	//body attributes
	        	result.body	 								= {};
	        	result.body.clientHeight	 				= document.body.clientHeight;
	        	result.body.offsetHeight	 				= document.body.offsetHeight;
	        	result.body.scrollHeight	 				= document.body.scrollHeight;
	        	result.body.clientWidth	 					= document.body.clientWidth;
	        	result.body.offsetWidth	 					= document.body.offsetWidth;
	        	result.body.scrollWidth	 					= document.body.scrollWidth;
	        
	        	result.documentElement						= {};
	        	result.documentElement.clientHeight	 		= document.documentElement.clientHeight;
	        	result.documentElement.scrollHeight	 		= document.documentElement.scrollHeight;
	        	result.documentElement.clientWidth	 		= document.documentElement.clientWidth;
	        	result.documentElement.scrollWidth	 		= document.documentElement.scrollWidth;

	        	result.document	 							= {};
	        	//best guess of total document height and width
	        	result.document.width						= Math.max(result.body.clientWidth, result.body.offsetWidth, result.body.scrollWidth, result.documentElement.clientWidth, result.documentElement.scrollWidth);
	        	result.document.height						= Math.max(result.body.clientHeight, result.body.offsetHeight, result.body.scrollHeight, result.documentElement.clientHeight, result.documentElement.scrollHeight);
	        	return JSON.stringify(result);
	        	
        	} catch(e) {
        		return "Get Document Inside Evaluate. Error: " + String(e);
        	}
	    });
		
		if (result.match(/^Get Document Inside Evaluate/)) {
			cmdObj.result.error.msg		= result;
		} else {
			cmdObj.result.code			= 200;
			cmdObj.result.data.dom		= result;
		}
		writeReturn(cmdObj);
		processLoop();

	} catch(e) {
		cmdObj.result.error.msg		= "Failed to get document. Error: " + e;
		writeReturn(cmdObj);
		processLoop();
	}
}
function getCookies(cmdObj)
{
	try {
		//validate
		var windowObj	= getWindowByCommand(cmdObj);
		var result		= windowObj.pjsPage.cookies;

		cmdObj.result.code			= 200;
		cmdObj.result.data.dom		= JSON.stringify(result);
		writeReturn(cmdObj);
		processLoop();

	} catch(e) {
		cmdObj.result.error.msg		= "Failed to get cookies. Error: " + e;
		writeReturn(cmdObj);
		processLoop();
	}
}
function setCookie(cmdObj)
{
	try {
		
		//several active issues setting cookies, see:
		//https://github.com/ariya/phantomjs/issues/13409
		//https://github.com/ariya/phantomjs/issues/14047
		
		var windowObj	= getWindowByCommand(cmdObj);
		
		var nCookie			= {};
		nCookie.name		= cmdObj.cmd.options.name;
		nCookie.value		= cmdObj.cmd.options.value;
		nCookie.domain		= cmdObj.cmd.options.domain;
		nCookie.path		= cmdObj.cmd.options.path;
		nCookie.httponly	= cmdObj.cmd.options.httponly;
		nCookie.secure		= cmdObj.cmd.options.secure;
		nCookie.expires		= cmdObj.cmd.options.expires;

		var success		= windowObj.pjsPage.addCookie(nCookie);

//		if (success === true) {
			cmdObj.result.code		= 200;
			writeReturn(cmdObj);
			processLoop();
//		} else {
//			cmdObj.result.error.msg		= "Failed to set cookie";
//			writeReturn(cmdObj);
//			processLoop();
//		}

	} catch(e) {
		cmdObj.result.error.msg		= "Failed to set Url. Error: " + e;
		writeReturn(cmdObj);
		processLoop();
	}
}
function clickElement(cmdObj)
{
	try {

		//validate
		var windowObj	= getWindowByCommand(cmdObj);
		if (typeof cmdObj.cmd.options.selector == 'undefined') {
			throw "Selector must be set";
		}

		var selector	= cmdObj.cmd.options.selector;
		var result		= windowObj.pjsPage.evaluate(function(selector){
	        var element = document.querySelector(selector);
	        
	        if (element === null) {
	        	 return 'selectorNotExist';
	        } else {
	        	 element.click();
			     return 'selectorClicked';
	        }
	       
	    }, selector);
		
		if (result != 'selectorNotExist') {
			cmdObj.result.code			= 200;
			cmdObj.result.data.script	= result;
		} else {
			cmdObj.result.error.msg		= selector + " does not exist";
		}

		writeReturn(cmdObj);
		processLoop();

	} catch(e) {
		cmdObj.result.error.msg		= "Failed to focus selector. Error: " + e;
		writeReturn(cmdObj);
		processLoop();
	}
}
function mouseEventOnElement(cmdObj)
{
	try {

		//validate
		var windowObj	= getWindowByCommand(cmdObj);
		if (typeof cmdObj.cmd.options.selector == 'undefined') {
			throw "Selector must be set";
		} else if (typeof cmdObj.cmd.options.mouseEvent == 'undefined') {
			throw "Mouse event must be set";
		}

		var selector	= cmdObj.cmd.options.selector;
		var result		= windowObj.pjsPage.evaluate(function(selector){
	        var element = document.querySelector(selector);
	        
	        if (element === null) {
	        	 return 'selectorNotExist';
	        } else {
	        	
	        	try {
		        	
		        	//get center of the element location
		        	var rect 		= element.getBoundingClientRect();
		        	var vertical	= (((rect.bottom - rect.top) / 2) + rect.top);
		        	var horizontal	= (((rect.right - rect.left) / 2) + rect.left);

		        	var result			= {};
		        	result.vertical		= vertical;
		        	result.horizontal	= horizontal;
		      		        	
		        	return JSON.stringify(result);
		        	
	        	} catch(e) {
	        		return "Mouse Event Inside Evaluate. Error: " + String(e);
	        	}
	        }
	       
	    }, selector);

		if (result == 'selectorNotExist') {
			cmdObj.result.error.msg		= selector + " does not exist";
		} else if (result === null) {
			cmdObj.result.error.msg		= selector + " does not have a size we can perform a mouse event on";
		} else if (result.match(/^Mouse Event Inside Evaluate/)) {
			cmdObj.result.error.msg		= result;
		} else {
			
			var coords		= JSON.parse(result);
			
			if (coords.horizontal > 0 && coords.vertical > 0) {
				if (cmdObj.cmd.options.mouseEvent == 'up') {
					windowObj.pjsPage.sendEvent("mouseup", coords.horizontal, coords.vertical);
				} else if (cmdObj.cmd.options.mouseEvent == 'down') {
					windowObj.pjsPage.sendEvent("mousedown", coords.horizontal, coords.vertical);
				} else if (cmdObj.cmd.options.mouseEvent == 'move') {
					windowObj.pjsPage.sendEvent("mousemove", coords.horizontal, coords.vertical);
				} else if (cmdObj.cmd.options.mouseEvent == 'leftclick') {
					windowObj.pjsPage.sendEvent("click", coords.horizontal, coords.vertical, 'left');
				} else if (cmdObj.cmd.options.mouseEvent == 'leftdoubleclick') {
					windowObj.pjsPage.sendEvent("doubleclick", coords.horizontal, coords.vertical, 'left');
				} else if (cmdObj.cmd.options.mouseEvent == 'rightclick') {
					windowObj.pjsPage.sendEvent("click", coords.horizontal, coords.vertical, 'right');
				} else if (cmdObj.cmd.options.mouseEvent == 'rightdoubleclick') {
					windowObj.pjsPage.sendEvent("doubleclick", coords.horizontal, coords.vertical, 'right');
				} else {
					throw "Invalid mouse event: " + cmdObj.cmd.options.mouseEvent;
				}
				
				//in the clear
				cmdObj.result.code			= 200;
				
			} else {
				throw "Cannot execute mouse event: " + cmdObj.cmd.options.mouseEvent + "size is: " + coords.horizontal + ":" + coords.vertical;
			}
			
		}
		writeReturn(cmdObj);
		processLoop();

	} catch(e) {
		cmdObj.result.error.msg		= "Failed to execute mouse event on selector. Error: " + e;
		writeReturn(cmdObj);
		processLoop();
	}
}
function sendKeyPresses(cmdObj)
{
	try {

		//validate
		var windowObj	= getWindowByCommand(cmdObj);
		if (typeof cmdObj.cmd.options.keys == 'undefined') {
			throw "Keys must be set";
		} else if (typeof cmdObj.cmd.options.modifiers == 'undefined') {
			throw "Modifiers must be set";
		}

		var keyCount	= Object.keys(cmdObj.cmd.options.keys).length;
		var modCount	= Object.keys(cmdObj.cmd.options.modifiers).length;
		
		if (modCount > 0) {
			var modString	= "";
			for (var i=0; i < modCount; i++) {
				var modkey	= cmdObj.cmd.options.modifiers[i];
				
				if (modkey == "alt") {
					var modifier	= "0x08000000";
				} else if (modkey == "shift") {
					var modifier	= "0x02000000";
				} else if (modkey == "ctrl") {
					var modifier	= "0x04000000";
				} else if (modkey == "meta") {
					var modifier	= "0x10000000";
				} else if (modkey == "keypad") {
					var modifier	= "0x20000000";
				} else {
					throw "Invalid Modifier Key: " . modkey;
				}
				
				if (i > 0) {
					modString += " | " + modifier;
				} else {
					modString += modifier;
				}
			}
		} else {
			var modString	= 0;
		}

		//full list
		//src: https://github.com/ariya/phantomjs/commit/cab2635e66d74b7e665c44400b8b20a8f225153a
		for (var i=0; i < keyCount; i++) {
			var kp		= cmdObj.cmd.options.keys[i];
			var kpLow	= String(kp).toLowerCase();
			if (
				kpLow == "backspace"
				|| kpLow == "enter"
				|| kpLow == "delete"
				|| kpLow == "up"
				|| kpLow == "down"
				|| kpLow == "left"
				|| kpLow == "right"
				|| kpLow == "pageup"
				|| kpLow == "pagedown"
				|| kpLow == "numlock"
				|| kpLow == "tab"
				|| kpLow == "braceleft"
				|| kpLow == "braceright"
				|| kpLow == "bracketleft"
				|| kpLow == "bracketright"
				|| kpLow == "f1"
				|| kpLow == "f2"
				|| kpLow == "f3"
				|| kpLow == "f4"
				|| kpLow == "f5"
				|| kpLow == "f6"
				|| kpLow == "f7"
				|| kpLow == "f8"
				|| kpLow == "f9"
				|| kpLow == "f10"
				|| kpLow == "f11"
				|| kpLow == "f12"
			) {
				var result	= windowObj.pjsPage.sendEvent("keypress", windowObj.pjsPage.event.key[kp], modString);
			} else {
				var result	= windowObj.pjsPage.sendEvent("keypress", kp, modString);
			}
		}

		//result is undefined, dont know how we could validate the job was done
		cmdObj.result.code			= 200;
		writeReturn(cmdObj);
		processLoop();

	} catch(e) {
		cmdObj.result.error.msg		= "Failed to send Key presses. Error: " + e;
		writeReturn(cmdObj);
		processLoop();
	}
}
function focusElement(cmdObj)
{
	try {

		//validate
		var windowObj	= getWindowByCommand(cmdObj);
		if (typeof cmdObj.cmd.options.selector == 'undefined') {
			throw "Selector must be set";
		}

		var selector	= cmdObj.cmd.options.selector;
		var result	= windowObj.pjsPage.evaluate(function(selector){
	        var element = document.querySelector(selector);
	        
	        if (element === null) {
	        	 return 'selectorNotExist';
	        } else {
	        	 element.click();
			     element.focus();
			     return 'selectorFocused';
	        }
	       
	    }, selector);
		
		if (result != 'selectorNotExist') {
			cmdObj.result.code			= 200;
			cmdObj.result.data.script	= result;
		} else {
			cmdObj.result.error.msg		= selector + " does not exist";
		}
		writeReturn(cmdObj);
		processLoop();

	} catch(e) {
		cmdObj.result.error.msg		= "Failed to focus selector. Error: " + e;
		writeReturn(cmdObj);
		processLoop();
	}
}
function JSCallFunction(cmdObj)
{
	try {
		
		//validate
		var windowObj	= getWindowByCommand(cmdObj);
		if (typeof cmdObj.cmd.options.functionName == 'undefined') {
			throw "Function name must be set";
		}

		var funcName	= cmdObj.cmd.options.functionName;
		var result		= windowObj.pjsPage.evaluate(function(funcName) {
				if (typeof window[funcName] == 'function') {
					var result = window[funcName]();
					//add check that the result can be serialized and returned out of eval, objects cannot be returned 
					return result;
				} else {
					return 'FunctionDoesNotExistBLA433';
				}
			}, funcName);
		
		if (result != 'FunctionDoesNotExistBLA433') {
			cmdObj.result.code			= 200;
			cmdObj.result.data.script	= result;
		} else {
			cmdObj.result.error.msg		= funcName + " is not a valid function name";
		}
		writeReturn(cmdObj);
		processLoop();

	} catch(e) {
		cmdObj.result.error.msg		= "Failed to call JS function. Error: " + e;
		writeReturn(cmdObj);
		processLoop();
	}
}
function loadJS(cmdObj)
{
	try {
		
		//validate
		var windowObj	= getWindowByCommand(cmdObj);
		if (typeof cmdObj.cmd.options.scriptPath == 'undefined') {
			throw "Script path must be set";
		}

		var result	= windowObj.pjsPage.injectJs(cmdObj.cmd.options.scriptPath);
		if (result === true) {
			cmdObj.result.code			= 200;		
		} else {
			cmdObj.result.error.msg		= "Script Failed to Load";
		}
		writeReturn(cmdObj);
		processLoop();

	} catch(e) {
		cmdObj.result.error.msg		= "Failed to load java script. Error: " + e;
		writeReturn(cmdObj);
		processLoop();
	}
}
function getDom(cmdObj)
{
	try {
		
		//validate
		var windowObj				= getWindowByCommand(cmdObj);

		cmdObj.result.data.dom		= encodeURIComponent(windowObj.pjsPage.content);
		cmdObj.result.code			= 200;
		writeReturn(cmdObj);
		processLoop();

	} catch(e) {
		cmdObj.result.error.msg		= "Failed to get DOM. Error: " + e;
		writeReturn(cmdObj);
		processLoop();
	}
}
function screenshot(cmdObj)
{
	try {
		
		//validate
		var windowObj	= getWindowByCommand(cmdObj);
		if (typeof cmdObj.cmd.options.imgFormat == 'undefined') {
			throw "Image format must be set";
		} else if (
			cmdObj.cmd.options.imgFormat != 'png'
			&& cmdObj.cmd.options.imgFormat != 'jpeg'
		) {
			throw "Invalid image format: " + cmdObj.cmd.options.imgFormat;
		}

		cmdObj.result.data.image	= windowObj.pjsPage.renderBase64(cmdObj.cmd.options.imgFormat);
		cmdObj.result.code			= 200;
		writeReturn(cmdObj);
		processLoop();

	} catch(e) {
		cmdObj.result.error.msg		= "Failed to take screenshot. Error: " + e;
		writeReturn(cmdObj);
		processLoop();
	}
}
function setDebug(cmdObj)
{
	try {
		//this command does not need a window
		
		//validate
		if (typeof cmdObj.cmd.options.debug == 'undefined') {
			throw "debug must be set";
		} else {
			if (cmdObj.cmd.options.debug == 1) {
				if (typeof cmdObj.cmd.options.debugPath == 'undefined') {
					throw "When debug is enabled, a debug path must be set";
				}
			}
		}
		
		//configure class
		if (cmdObj.cmd.options.debug == 1) {
			classData.debug			= true;
			classData.debugPath		= cmdObj.cmd.options.debugPath;
			writeDebug(arguments.callee.name, "Debug Enabled");
			
		} else {
			if (classData.debug === true) {
				writeDebug(arguments.callee.name, "Disabling Debug");
			}
			classData.debug			= false;
			classData.debugPath		= "";
		}

		//return the command
		cmdObj.result.code		= 200;
		writeReturn(cmdObj);
		processLoop();

	} catch(e) {
		cmdObj.result.error.msg		= "Failed to set debug. Error: " + e;
		writeReturn(cmdObj);
		processLoop();
	}
}
function initialize(cmdObj)
{
	try {
		//this command does not need a window
		
		//validate
		if (typeof cmdObj.cmd.options.stdInPath == 'undefined') {
			throw "stdIn path must be set";
		} else {
			classData.stdIn.path	= cmdObj.cmd.options.stdInPath;
		}
		
		if (typeof cmdObj.cmd.options.terminationSecs != 'undefined') {
			//update the termination epoch
			terminationEpoch		= (getEpoch(false) + parseInt(cmdObj.cmd.options.terminationSecs));
		}

		//return the command
		cmdObj.result.PID		= system.pid;
		cmdObj.result.code		= 200;
		writeReturn(cmdObj);

		classData.initialized	= true;
		
	} catch(e) {
		classData.initialized	= false;
		var eMsg	= "Failed to initialize";
		writeError(e, eMsg);
	}
	
	processLoop();
}
function terminate(cmdObj)
{
	try {
		//return the command
		if (cmdObj !== null) {
			cmdObj.result.code		= 200;
			writeReturn(cmdObj);
		}
		setTimeout(function() {
			//give time to pickup return
			//we are sure to exit successfully now
			phantom.exit(1);
		}, 4000);
	} catch(e) {
		phantom.exit(1);
	}
}


//utilities
function commandWaitForWindowLoad(cmdObj, callBackFunc)
{
	try {
		var windowObj	= getWindowByCommand(cmdObj);
		if (windowObj.loading === true) {
			cmdObj.result.stats.pageLoadWait += classData.loadWaitInterval;
			setTimeout(function() {
				commandWaitForWindowLoad(cmdObj, callBackFunc);
			}, classData.loadWaitInterval);
		} else {
			//command ready to trigger
			callBackFunc(cmdObj);
			if (classData.debug === true) {
				if (cmdObj.result.stats.pageLoadWait > 0) {
					writeDebug(arguments.callee.name, "Command: " + cmdObj.cmd.name + ", Waited: " + cmdObj.result.stats.pageLoadWait + " for page to finish loading");
				}
			}
		}
	
	} catch(e) {
		var eMsg	= "Failed command wait for load";
		writeError(e, eMsg);
		//dont write out the command, timeout in cmdExe() handles that
	}
}
function getWindowByCommand(cmdObj)
{
	try {
		//return an existing window based on the command uuid
		//saves a ton of validations in every action function
		if (typeof cmdObj.cmd.window == 'undefined') {
			throw "Window must be defined in command";
		} else if (typeof cmdObj.cmd.window.UUID == 'undefined') {
			throw "Window UUID must be set";
		}
	
		var windowObj	= getWindowByUUID(cmdObj.cmd.window.UUID);
		if (windowObj === false) {
			windowObj	= getNewWindow(cmdObj.cmd.window.UUID);
			configurePage(windowObj);
		}
		
		//make sure the window attributes are synced with the command
		
		//viewport
		var changeViewPort	= false;
		var viewPortWidth	= windowObj.pjsPage.viewportSize.width;
		var viewPortHeight	= windowObj.pjsPage.viewportSize.height;
		
		if (typeof cmdObj.cmd.window.width != 'undefined') {
			if (cmdObj.cmd.window.width != viewPortWidth) {
				changeViewPort	= true;
				viewPortWidth	= cmdObj.cmd.window.width;
			}
		}
		if (typeof cmdObj.cmd.window.height != 'undefined') {
			if (cmdObj.cmd.window.height != viewPortHeight) {
				changeViewPort	= true;
				viewPortHeight	= cmdObj.cmd.window.height;
			}
		}
		if (changeViewPort === true) {
			if (classData.debug === true) {
				writeDebug(arguments.callee.name, "Changing viewPort from " + windowObj.pjsPage.viewportSize.width + ":" + windowObj.pjsPage.viewportSize.height + " to " + viewPortWidth + ":" + viewPortHeight);
			}
			windowObj.pjsPage.viewportSize	= { width: viewPortWidth, height: viewPortHeight };
		}
		
		//clipRect
		if (typeof cmdObj.cmd.window.raster != 'undefined') {
	
			var changeRaster	= false;
			var rasterTop		= windowObj.pjsPage.clipRect.top;
			var rasterLeft		= windowObj.pjsPage.clipRect.left;
			var rasterWidth		= windowObj.pjsPage.clipRect.width;
			var rasterHeight	= windowObj.pjsPage.clipRect.height;
			
			if (typeof cmdObj.cmd.window.raster.top != 'undefined') {
				if (cmdObj.cmd.window.raster.top != rasterTop) {
					changeRaster	= true;
					rasterTop		= cmdObj.cmd.window.raster.top;
				}
			}
			if (typeof cmdObj.cmd.window.raster.left != 'undefined') {
				if (cmdObj.cmd.window.raster.left != rasterLeft) {
					changeRaster	= true;
					rasterLeft		= cmdObj.cmd.window.raster.left;
				}
			}
			if (typeof cmdObj.cmd.window.raster.width != 'undefined') {
				if (cmdObj.cmd.window.raster.width != rasterWidth) {
					changeRaster	= true;
					rasterWidth		= cmdObj.cmd.window.raster.width;
				}
			}
			if (typeof cmdObj.cmd.window.raster.height != 'undefined') {
				if (cmdObj.cmd.window.raster.height != rasterHeight) {
					changeRaster	= true;
					rasterHeight	= cmdObj.cmd.window.raster.height;
				}
			}
			if (changeRaster === true) {
				if (classData.debug === true) {
					writeDebug(arguments.callee.name, "Changing raster area from " + windowObj.pjsPage.clipRect.top + ":" + windowObj.pjsPage.clipRect.left + ":" + windowObj.pjsPage.clipRect.width + ":" + windowObj.pjsPage.clipRect.height + " to " +rasterTop + ":" + rasterLeft + ":" + rasterWidth + ":" + rasterHeight);
				}
				windowObj.pjsPage.clipRect	= { top: rasterTop, left: rasterLeft, width: rasterWidth, height: rasterHeight };
			}
		}
		
		//load images
		if (typeof cmdObj.cmd.window.loadImages != 'undefined') {
			var curLoadImgs	= windowObj.pjsPage.settings.loadImages;
			var cmdLoadImgs	= true;
			if (cmdObj.cmd.window.loadImages == 0) {
				cmdLoadImgs	= false;
			}
	
			if (cmdLoadImgs != curLoadImgs) {
				windowObj.pjsPage.settings.loadImages = cmdLoadImgs;
				if (classData.debug === true) {
					writeDebug(arguments.callee.name, "Changing loading of images to: " + cmdLoadImgs);
				}
			}
		}
		
		//user agent
		if (typeof cmdObj.cmd.window.userAgent != 'undefined') {
			var curUserAgent	= windowObj.pjsPage.settings.userAgent;
			var cmdUserAgent	= cmdObj.cmd.window.userAgent;
			
			if (cmdUserAgent != "" && curUserAgent != cmdUserAgent) {
				windowObj.pjsPage.settings.userAgent = cmdUserAgent;
				if (classData.debug === true) {
					writeDebug(arguments.callee.name, "Changing User Agent from:\n" + curUserAgent + "\nto:\n" + cmdUserAgent);
				}
			}
		}
		
		//scrollPosition
		if (typeof cmdObj.cmd.window.scroll != 'undefined') {
			var changeScrollPos	= false;
			var scrollTop		= windowObj.pjsPage.scrollPosition.top;
			var scrollLeft		= windowObj.pjsPage.scrollPosition.left;
			
			if (typeof cmdObj.cmd.window.scroll.top != 'undefined') {
				if (cmdObj.cmd.window.scroll.top != scrollTop) {
					changeScrollPos	= true;
					scrollTop		= cmdObj.cmd.window.scroll.top;
				}
			}
			if (typeof cmdObj.cmd.window.scroll.left != 'undefined') {
				if (cmdObj.cmd.window.scroll.left != scrollLeft) {
					changeScrollPos	= true;
					scrollLeft		= cmdObj.cmd.window.scroll.left;
				}
			}
			if (changeScrollPos === true) {
				if (classData.debug === true) {
					writeDebug(arguments.callee.name, "Changing scroll position from " + windowObj.pjsPage.scrollPosition.top + ":" + windowObj.pjsPage.scrollPosition.left + " to " + scrollTop + ":" + scrollLeft);
				}
				windowObj.pjsPage.scrollPosition	= { top: scrollTop, left: scrollLeft };
			}
		}

		return windowObj;
		
	} catch(e) {
		var eMsg	= "Failed to get window by command";
		writeError(e, eMsg);
		//dont write out the command, timeout in cmdExe() handles that
	}
}
function getWindowByUUID(uuid)
{
	var winLen	= classData.windows.length;
	if (winLen > 0) {
		for (var i=0; i < winLen; i++) {
			var windowObj	= classData.windows[i];
			if (windowObj.uuid == uuid) {
				return windowObj;
			}
		}
	}
	
	//no match
	return false;
}
function getNewWindow(uuid)
{
	var newWindow				= {};
	newWindow.uuid				= uuid;
	newWindow.loading			= false;
	newWindow.errors			= {};
	newWindow.errors.resource	= "";
	newWindow.errors.general	= "";
	
	newWindow.parent			= null;
	newWindow.children			= [];

	classData.windows.push(newWindow);
	
	return newWindow;
}

function configurePage(windowObj)
{
	try {
	
		if (typeof windowObj.pjsPage == 'undefined') {
			windowObj.pjsPage			= webpage.create();
		}
		if (typeof windowObj.pjsPage.uuid == 'undefined') {
			//assign internal UUID
			windowObj.pjsPage.uuid		= getUUID();
		}
	
		//set default functions
		windowObj.pjsPage.onLoadStarted = function() {
			try {
				windowObj.loading	= true;
			} catch(e) {
				var eMsg	= "Failure in onLoadStarted for UUID: " + windowObj.pjsPage.uuid;
				writeError(e, eMsg);
			}
		};
		//page completed load
		windowObj.pjsPage.onLoadFinished = function(status) {
			
			try {
				windowObj.loading = false;
				
				//make sure the scroll is corrected, it is not enough that the window is configured
				//if scroll is set and then the page is loaded scroll will not have taken effect
				var scrollTop						= windowObj.pjsPage.scrollPosition.top;
				var scrollLeft						= windowObj.pjsPage.scrollPosition.left;
				windowObj.pjsPage.scrollPosition	= { top: scrollTop, left: scrollLeft };
			} catch(e) {
				var eMsg	= "Failure in onLoadFinished for UUID: " + windowObj.pjsPage.uuid;
				writeError(e, eMsg);
			}
		};
			
			//error handling
		windowObj.pjsPage.onResourceError = function(resourceError) {
			try {
				windowObj.errors.resource = resourceError.errorString;
			} catch(e) {
				var eMsg	= "Failure in onResourceError for UUID: " + windowObj.pjsPage.uuid;
				writeError(e, eMsg);
			}
		};
		
		windowObj.pjsPage.onError = function(msg, trace) {
			try {
				windowObj.errors.general	= msg;
			} catch(e) {
				var eMsg	= "Failure in onError for UUID: " + windowObj.pjsPage.uuid;
				writeError(e, eMsg);
			}
		};
		
		windowObj.pjsPage.onPageCreated = function(childPage) {
	
			try {
				var childUUID					= getUUID();
				
				var childWindowObj				= getNewWindow(childUUID);
				childWindowObj.pjsPage			= childPage;
				configurePage(childWindowObj);
				
				childWindowObj.parent			= windowObj;
				windowObj.children.push(childWindowObj);
				
				if (classData.debug === true) {
					writeDebug(arguments.callee.name, "Page: " + windowObj.uuid + ", conceived a child named: " + childWindowObj.uuid);
				}
			} catch(e) {
				var eMsg	= "Failure in onPageCreated for UUID: " + windowObj.pjsPage.uuid;
				writeError(e, eMsg);
			}
		};
		windowObj.pjsPage.onClosing = function(closingPage) {
			try {
				//need to add logic to remove child objects from parents
				if (classData.debug === true) {
					writeDebug(arguments.callee.name, "Child Closed");
				}
			} catch(e) {
				var eMsg	= "Failure in onClosing for UUID: " + windowObj.pjsPage.uuid;
				writeError(e, eMsg);
			}
		};
		
		windowObj.pjsPage.onResourceReceived = function(response) {
	
			try {
				
			} catch(e) {
				var eMsg	= "Failure in onResourceReceived for UUID: " + windowObj.pjsPage.uuid;
				writeError(e, eMsg);
			}
		};
	
		//viewport, clipRect, scrollPosition will have default values
		//and they are dictated from control
		
	} catch(e) {
		var eMsg	= "Failed to Configure Page";
		writeError(e, eMsg);
	}
}
function getCommand()
{
	try {
		
		var cmdLen	= classData.cmdStack.length;
		
		if (cmdLen == 0) {
			
			var rData		= stdInRead();
			if (rData !== null) {
				var cmdLines	= rData.split("cmdStart>>>");
				
				for (var i=0; i < cmdLines.length; i++) {
					var cmdLine	= cmdLines[i].trim();
					
					if (cmdLine != "") {
						
						var cmdEndPos 						= cmdLine.indexOf("<<<cmdEnd");
						var rawCmd							= atob(cmdLine.substring(0, cmdEndPos));
						
						var cmdObj							= JSON.parse(rawCmd);
						cmdObj.result						= {};
						cmdObj.result.returned				= false;
						cmdObj.result.code					= 0;
						cmdObj.result.data					= {};
						cmdObj.result.data.image			= "";
						cmdObj.result.data.dom				= "";
						cmdObj.result.data.script			= "";
						cmdObj.result.error					= {};
						cmdObj.result.error.msg				= "";
						cmdObj.result.error.code			= 0;
						cmdObj.result.stats					= {};
						cmdObj.result.stats.pageLoadWait	= 0;
						
						cmdObj.result.parent				= null;
						cmdObj.result.children				= [];
						
						classData.cmdStack.push(cmdObj);
					}
				}
				
				cmdLen	= classData.cmdStack.length;
			}
		}
		
		if (cmdLen > 0) {
			//there are commands pending
			return classData.cmdStack.shift();
		} else {
			return false;
		}
		
	} catch(e) {
		var eMsg	= "Failed to get command";
		writeError(e, eMsg);
		terminate(null);
	}
}
function getUUID()
{
  function s4()
  {
    return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
  }
  return s4() + s4() + '_' + s4() + '_' + s4() + '_' + s4() + '_' + s4() + s4() + s4();
}
function getEpoch(mili)
{
	if (mili === false) {
		return Math.floor((new Date).getTime()/1000);
	} else {
		return (Math.floor((new Date).getTime()) / 1000);
	}
}
function forceTermination()
{
	setTimeout(function() {
		var rTime	= (terminationEpoch - getEpoch(false));
		if (rTime < 0) {
			if (classData.debug === true) {
				writeDebug(arguments.callee.name, "Timeout Terminating");
			}
			//hard exit, there is no command to send
			phantom.exit(1);
		} else {
			forceTermination();
		}
	}, 1000);
}

//Read Write

function stdInRead()
{
	try {
		
		//stdIn is blocking and will hang the process
		//there is not yet a Async method available, so I have opted
		//to send a delimitor to stdIn, once that delimiter has been 
		//reached there is no more data to read. That way we never end up blocked

		if (classData.initialized === true) {
			var delimitor		= "[" + getUUID() + "]";
			fSystem.write(classData.stdIn.path, delimitor, 'a');
		} else {
			//the init command will always end in 2 new lines
			var delimitor		= "\n\n";
		}
		
		var dLen		= delimitor.length;

		var newData		= "";
		var done		= false;
		var i=1;
		while(done === false) {
			i++;
			
			newData	+= system.stdin.read(1);
			
			if (i > dLen) {
				var dPos 	= newData.indexOf(delimitor);
				if (dPos != -1) {
					var done		= true;
					if (dPos == 0) {
						return null;
					} else {
						return newData.substring(0, dPos);
					}
				}
			}
		}

	} catch(e) {
		var eMsg	= "Failed to read from stdIn";
		writeError(e, eMsg);
	}
}
function stdOutWrite(data)
{
	try {
		system.stdout.write(data);
	} catch(e) {
		//hard exit
		phantom.exit(1);
	}
}
function stdErrWrite(data)
{
	try {
		system.stderr.write(data);
	} catch(e) {
		phantom.exit(1);
	}
}
function postCmdProcessing(cmdObj)
{
	try {

		var windowObj	= getWindowByUUID(cmdObj.cmd.window.UUID);
		if (windowObj !== false) {
			
			//add parent
			if (windowObj.parent !== null) {
				cmdObj.result.parent	= windowObj.parent.uuid;
			}
			
			//add children
			var childCount	= windowObj.children.length;
			for (var i=0; i < childCount; i++) {
				var childObj				= windowObj.children[i];
				
				var child					= {};
				child.window				= {};
				child.window.uuid			= childObj.uuid;
				child.window.width			= childObj.pjsPage.viewportSize.width;
				child.window.height			= childObj.pjsPage.viewportSize.height;
				
				child.window.raster			= {};
				child.window.raster.top		= childObj.pjsPage.clipRect.top;
				child.window.raster.left	= childObj.pjsPage.clipRect.left;
				child.window.raster.width	= childObj.pjsPage.clipRect.width;
				child.window.raster.height	= childObj.pjsPage.clipRect.height;
				
				cmdObj.result.children.push(child);
			}
		}

	} catch(e) {
		var eMsg	= "Failed to perform post command processing";
		writeError(e, eMsg);
	}
}
function writeReturn(cmdObj)
{
	try {
		
		postCmdProcessing(cmdObj);
		
		var jsonStr	= JSON.stringify(cmdObj);
		var rData	= "cmdStartReturn>>>" + btoa(jsonStr) + "<<<cmdEndReturn\n\n";
		stdOutWrite(rData);
		cmdObj.result.returned	= true;
		
	} catch(e) {
		var eMsg	= "Failed to write return";
		writeError(e, eMsg);
	}
}
function writeError(e, eMsg, eCode)
{
	try {
		
		errObj				= {};
		errObj.error		= {};
		errObj.error.raw	= e;
		errObj.error.msg	= eMsg;
		errObj.error.code	= eCode;
		
		var jsonStr	= JSON.stringify(errObj);
		var rData	= "errStart>>>" + btoa(jsonStr) + "<<<errEnd\n\n";
		stdErrWrite(rData);
		
		if (classData.debug === true) {
			//relay errors to the debugger as clear text
			writeDebug(arguments.callee.name, "Error: " + e + "\nMsg: " + eMsg + "\nCode: " + eCode);
		}
		
	} catch(e) {
		var eMsg	= "Failed to write error. Error: " + e + "\n";
		stdErrWrite(eMsg);
	}
}
function writeDebug(funcName, data)
{
	try {
		//NFSMKO UIF NBHKD
		fSystem.write(classData.debugPath, "debugStart>>>" + getEpoch(true) + " - " + funcName + "\n" + data + "<<<debugEnd\n", 'a');
	} catch(e) {
		var eMsg	= "Failed to write debug";
		writeError(e, eMsg);
	}
}