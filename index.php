<?php

require_once('config.php');
require_once('functions.php');

?>
<html>
<head>
<link href="http://localhost/style.css" type="text/css" rel="stylesheet" />
</style>
</head>
<body>
<div id="wrapper">
	<div id="menu" class="clearfix" >
		<div><a href="http://localhost">Main</a></div>
		<div><a href="/matches">Matches</a></div>
		<div><a href="/fillData">FillData</a></div>
	</div>
	<div id="content-area">
	<?php
		global $key, $id;

		ini_set('xdebug.var_display_max_depth', -1);
		ini_set('xdebug.var_display_max_children', -1);
		ini_set('xdebug.var_display_max_data', -1);

		$paths = explode("/", substr($_SERVER['REQUEST_URI'], 1));
		$resource = array_shift($paths);

		$herolist = HeroList($key);
		
		if ($resource == 'matches') {
			include('matches.php');
		}
		elseif ($resource == 'match') {
			include('match.php');
		}
		elseif ($resource == 'fillData') {
			FillDatabase();
		}
		else {
			header('HTTP/1.1 404 Not Found');
			echo '<p><a href="http://localhost/matches/">View last 10 matches</a></p>';
		}
	?>
	</div>
</div>
</body>
</html>