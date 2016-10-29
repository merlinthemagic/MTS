$workPath	= $args[0];

$stdIn		= $workPath + "\stdIn";
$stdOut		= $workPath + "\stdOut";
$stdErr		= $workPath + "\stdErr";

#when we did not get a command, how long should we wait before 
#checking again
$waitDelay	= 10;

#debug data
$debug		= 0;
$debugFile	= "";

$cmdObj		= "";
$run		= 1;

#Init system

#do not clear stdin there may already be a pending command
Clear-Content "$stdOut";
Clear-Content "$stdErr";

#We are using json to send and receive commands, but the json-decode function
#is only available in ps v3, so we use the web module instead since it is likely to
#already be installed
add-type -assembly system.web.extensions;
$serObj	= new-object system.web.script.serialization.javascriptSerializer;

$i=0;
DO {
	$i++;
	
	try {

		$encCMD	= Get-Content "$stdIn";
		if (![string]::IsNullOrEmpty($encCMD)) {
			
			#got something, is it a full command?
			$getCmdRegex	= [regex]"cmdStart>>>(.*)<<<cmdEnd";
			$cmdParts		= $getCmdRegex.Match($encCMD);
			
			if (![string]::IsNullOrEmpty($cmdParts)) {
				
				#got a full command, decode it
				$b64Cmd		= $cmdParts.Groups[1].Value;
				$jsonStr	= [System.Text.Encoding]::UTF8.GetString([System.Convert]::FromBase64String($b64Cmd));
				$cmdObj		= $serObj.DeserializeObject($jsonStr);
				
				#clear stdIn so we do not pickup the same command again
				Clear-Content "$stdIn";
			}
		}
	
		if (![string]::IsNullOrEmpty($cmdObj)) {
		
				$nextCmdObj	= $cmdObj;
				$cmdObj		= "";
				
				if ($debug -eq 1) {
					$cName	= [string]$nextCmdObj.cmd.name;
					Write-Host "Processing Command Name: $cName";
				}
				
				$cmdStr	= [string]$nextCmdObj.cmd.string;
				
				if ($cmdStr -eq "mtsTerminate") {
				
					$rData		= "terminating";
					$run		= 0;

				} else {
					try {
				
						$rData		= Invoke-Expression $cmdStr | Out-String;
					
					} catch {
						#command failed, but not because of an error in the program
						#its a user command problem that should not go in stdErr
						$rData		= $_.Exception.Message;
						
					}
				}
				
				$rEncData	= [System.Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($rData));

				##expand the command object before returning it
				$nextCmdObj.result			= @{};
				$nextCmdObj.result.data		= $rEncData;

				$rJson		= $serObj.Serialize($nextCmdObj) | Out-String;
				$rJsonEnc	= [System.Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($rJson));
				
				#enforce UTF8 encoding or powershell defaults to the system's current ANSI code page.
				"cmdReturnStart>>>$rJsonEnc<<<cmdReturnEnd" | Out-File -Append "$stdOut" -Encoding UTF8;

				if ($debug -eq 1) {
					$cName	= [string]$nextCmdObj.cmd.name;
					Write-Host "Completed Command Name: $cName";
				}
				
				#free up mem
				$nextCmdObj	= "";
				$rJson		= "";
				$rJsonEnc	= "";
				$rData		= "";

		} else {
			#No pending command, wait a bit
			Start-Sleep -m $waitDelay;
		}

	} catch {
	
		$rData		= $_.Exception.Message;
		#$rEncData	= [System.Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($rData));
		$rEncData	= $rData;
		"errorStart>>>$rEncData<<<errorEnd" | Out-File -Append "$stdErr" -Encoding UTF8;
	}

} While ($run)