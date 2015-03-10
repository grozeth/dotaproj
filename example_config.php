<?php
	// VARIABLES
	$id = "";	// 32 bit steam id 
	$key = ""; 	// Get the api key from http://steamcommunity.com/dev/apikey
	// VARIABLES_END
	
	function ConnectDatabase() {
	$srvrname = ""; 	// database url / ip
	$uname = ""; 		// database username
	$pword = ""; 		// database password
	$dbname = ""; 		// database to use
	
	return new mysqli($srvrname, $uname, $pword, $dbname);
}
?>