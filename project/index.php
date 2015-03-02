<html>
<head></head>
<body>

<?php
$paths = $_SERVER['REQUEST_URI'];
echo $paths;
$resource = array_shift($paths);
if ($resource != 'test') {
	echo $resource;
	// VARIABLES
	$id = '76561197973199992';
	$key = 'AD3433F4B0A0E2996580E4E8321BB2C9';
	
	// VARIABLES_END
	
	// API CALL
	$curl = curl_init();
	
	curl_setopt($curl, CURLOPT_URL, 'https://api.steampowered.com/IDOTA2Match_570/GetMatchHistory/V001/?key='.$key.'&account_id='.$id);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	$result = curl_exec($curl);
	$result_json = json_decode($result);
	echo $result_json;
	if(!curl_exec($curl)){
		die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
	}
	curl_close();
	// API CALL_END
	
	// TO BE INSERTED
}
else {
	header('HTTP/1.1 404 Not Found');
	echo 'You fucked up';
}
	
?>
</body>
</html>