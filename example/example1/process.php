<?php  if(isset($_POST['name'])) $name = $_POST['name'];
	elseif(isset($args[1])) $name= $args[1];
	else die('no name');
	echo $name; 
	file_put_contents('file/'. $name .".txt", $name);
?>