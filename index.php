<html>
<head>
<style>
a {color: #000000;}
.match { border: 1px solid #eee; padding: 0 1em; background: #333; color: #eee; margin: 1em 0;}
.match a, a:active, a:hover { color: #eee; }
	
.match .radiant { color: #55ff55; padding: 1em; }
.match .radiant p{ font-weight: bold; }
.match .dire { color: #ff5555; padding: 1em; }
.match .dire p{ font-weight: bold; }
td { padding: 0.5em; }
.win {color: #55ff55; text-decoration: none;}
.lose { color: #ff5555; text-decoration: none;}
</style>
</head>
<body>
<?php

require_once('config.php');

function FillDatabase() {
	global $key, $id, $paths;
	
	$connection = ConnectDatabase();
	
	if ($connection->connect_error) {
			die("Connection failed: " . $connection->connect_error());
	}
	
	$matches = MatchHistory($key, $id, $paths);
	
	foreach ($matches['matches'] as $match) {
		$match_details = MatchDetails($match["match_id"]);
		$sql = "INSERT INTO matches (duration, radiant_win, game_mode, lobby, match_id, first_blood_time)
			VALUES ('".$match_details['duration']."', '".$match_details['radiant_win']."', '".$match_details['game_mode']."', '".$match_details['lobby_type']."', '".$match['match_id']."', '".$match_details['first_blood_time']."')";
		
		if($connection->query($sql) === TRUE) {
			$sql_players = "INSERT INTO players (match_id, account_id, player_slot, hero_id, kills, deaths, assists, last_hits, denies, xp_per_min, gold_per_min, hero_damage, tower_damage, hero_healing, level)  VALUES";
			$counter = 1;
			foreach ($match_details['players'] as $player) {
				$spacer = ($counter == 1) ? " " : ", ";
				$sql_players .= $spacer."('".$match_details['match_id']."', '".$player['account_id']."', '".$player['player_slot']."', '".$player['hero_id']."', '".$player['kills']."', '".$player['deaths']."', '".$player['assists']."', '".$player['last_hits']."', '".$player['denies']."', '".$player['xp_per_min']."', '".$player['gold_per_min']."', '".$player['hero_damage']."', '".$player['tower_damage']."', '".$player['hero_healing']."', '".$player['level']."')";
				$counter++;
			}
			if ($connection->query($sql_players) === TRUE) {
				echo "Success!<br/>";
			}
			else {
				echo "Error!<br/>";
			}
		}
		else {
			echo "ERROR: " . $sql . "<br/>" . $connection->error ."<br/>";
		}
	}
	
	$connection->close();
}

function fetchMatchData($match_id) {
	$connection = ConnectDatabase();
	
	$query = "SELECT * FROM matches WHERE match_id = '".$match_id."'";
	
	$m_result = $connection->query($query);
	
	$result = mysqli_fetch_assoc($m_result);
	
	$query = "SELECT * FROM players WHERE match_id = '".$match_id."'";
	$p_result = $connection->query($query);
	while($player = mysqli_fetch_assoc($p_result)) {
		$result['players'][] = $player;
	}
	
	$connection->close();
 	return $result;
}
function fetchMatches() {
	global $id, $paths;
	$start_point = 10;
	$start_point += !empty($paths[0]) ? $paths[0] : '0';
	$limit = 10;
	$connection = ConnectDatabase();
	$records = $connection->query("SELECT COUNT(*)as records FROM matches");
	$records = (mysqli_fetch_assoc($records));
	$offset = $records['records'] - $start_point;
	if ($offset < 0) {
		$offset = 0;
		$limit = $records['records']-($start_point-10);
	}
	$query = "SELECT match_id, duration, radiant_win, game_mode FROM matches  LIMIT ".$offset.", ".$limit; 
	
	$m_result = $connection->query($query);
	$team = array(0, 1, 2, 3, 4);
	$i = 0;
	while($row = mysqli_fetch_assoc($m_result)) {
			$result['matches'][$i] = $row;
			$query = "SELECT hero_id, player_slot FROM players WHERE match_id = '".$row['match_id']."' && account_id = '".$id."'";
			$p_result = $connection->query($query);
			$p_result = mysqli_fetch_assoc($p_result);
			$result['matches'][$i]['player_hero'] = parseHeroName($p_result['hero_id']);
			$result['matches'][$i]['player_team'] = (in_array($p_result['player_slot'], $team)) ? 1 : 0;
			$i++;
	}
	$result['matches_left'] = $offset;
	$result['matches'] = array_reverse($result['matches']);
	$connection->close();
	return ($result);
}

function MatchHistory ($key, $id, $paths) {
	$matches = !empty($paths[0]) ?  $paths[0] : '15';
	$hero_id = isset($paths[1]) ?  $paths[1] : '';
	$start_at = isset($paths[2]) ? $paths[2] : '';
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, 'https://api.steampowered.com/IDOTA2Match_570/GetMatchHistory/V001/?key='.$key.'&account_id='.$id.'&matches_requested='.$matches.'&hero_id='.$hero_id.'&start_at_match_id='.$start_at);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	$data = curl_exec($curl);
	$data = json_decode($data, true);
	curl_close($curl);
	return $data['result'];
}

function MatchDetails($match_id) {
	global $key, $paths;
	
	$match_id = empty($match_id) ? $paths[0] : $match_id;
	
	if($match_id) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'http://api.steampowered.com/IDOTA2Match_570/GetMatchDetails/v1?key='.$key.'&match_id='.$match_id);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($curl);
		$result = json_decode($result, true);
		curl_close($curl);
		return $result['result'];
	}
	else {
		echo "You fucked up, again!";
	}
}

function HeroList($key) {
	$curl = curl_init();
		
	curl_setopt($curl, CURLOPT_URL, 'http://api.steampowered.com/IEconDOTA2_570/GetHeroes/v1?key='.$key.'&language=en');
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	$heroes = curl_exec($curl);
	$heroes = json_decode($heroes, true);
	curl_close($curl);
	foreach ($heroes['result']['heroes'] as $hero) {
		$HeroMap[$hero['id']] = $hero['localized_name']; 
	}
	return $HeroMap;
}

function parseTeams ($match, $team) {
	global $id;
	if ($team == "Radiant") {
		$slots = array(0,1,2,3,4);
	}
	else {
		$slots = array(128,129,130,131,132);
	}
	$teamTotal['kills'] = 0;
	$teamTotal['deaths'] = 0;
	$teamTotal['assists'] = 0;
	
	foreach ($match['players'] as $player) {
		if (in_array($player['player_slot'], $slots)) {
			{ 
				$me = ($id == $player['account_id']) ? true : false;	
					if ($me) {
						echo '<b>';
					}
					echo parseHeroName($player['hero_id']).': '.$player['kills'].' / '.$player['deaths'].' / '.$player['assists'].' <br/>';
					if ($me) {
						echo '</b>';
					}
			}
			$teamTotal['kills'] += $player['kills'];
			$teamTotal['deaths'] += $player['deaths'];
			$teamTotal['assists'] += $player['assists'];
		}
	}
	echo '<hr/>';
	echo 'Total: '.$teamTotal['kills'].' / '.$teamTotal['deaths'].' / '.$teamTotal['assists'].' <br/>';
} 

function parseHeroName ($hero_id) {
	global $herolist;
	return $herolist[$hero_id];
}

global $key, $id;

ini_set('xdebug.var_display_max_depth', -1);
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);

$paths = explode("/", substr($_SERVER['REQUEST_URI'], 1));
$resource = array_shift($paths);
	
	$herolist = HeroList($key);
	
	if ($resource == 'matches') {
		$data = fetchMatches();
		echo "<table cellpadding='0' cellspacing='0' border='0'>
					<tr>";
		echo "<th>Hero</th>";
		echo "<th>ID</th>";
		echo "<th>Result</th>";
		echo "<th>Duration</th>";
		echo "</tr>";
		foreach ($data['matches'] as $match) {
			echo "<tr class='match'>";
			echo "<td>".$match['player_hero']."</td>";
			echo "<td><a href=\"http://localhost/match/".$match["match_id"]."\">".$match["match_id"]."</td>";
			$result = ($match['radiant_win'] == $match['player_team']) ? '<span class="win">Won match</span>' : '<span class="lose">Lost match</span>';
			echo "<td><a href=\"http://localhost/match/".$match["match_id"]."\">".$result."</a></td>";
			echo "<td>"; printf('%02d', floor($match['duration'] / 60)); echo ":"; printf('%02d', ($match['duration'] % 60)); echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
		if (!empty($paths[0])) {
			echo '<a href="http://localhost/matches/'.($paths[0]-10).'/">&laquo; prev</a>';
			if (!($data['matches_left'] == 0)) {
				echo '&nbsp;|&nbsp;<a href="http://localhost/matches/'.($paths[0]+10).'/">next &raquo;</a>';
			}
		}
		else {
			echo '<a href="http://localhost/matches/10/">next &raquo;</a>';
		}
		
	}
	elseif ($resource == 'match') {
		$match = fetchMatchData($paths[0]);
		echo "<div class='match'>";
			echo "<p>Match ID: <a href='http://www.dotabuff.com/matches/".$match['match_id']."' target='_blank'>".$match['match_id']."</a></p>";
			echo "<div class='radiant'>
					<p>Radiant";
				if ($match['radiant_win'] == 1) {
					echo " (Winner)";
				}
			echo "</p>";
				parseTeams($match, "Radiant");
			echo "</div>";
			echo "<div class='dire'>
					<p>Dire";
				if ($match['radiant_win'] == 0) {
					echo " (Winner)";
				}
			echo "</p>";
				parseTeams($match, "Dire");
			echo "</div>";
			echo "</div>";
	}
	elseif ($resource == 'fillData') {
		FillDatabase();
	}
	else {
		header('HTTP/1.1 404 Not Found');
		echo '<p><a href="http://localhost/matches/">View last 10 matches</a></p>';
	}
	
?>
</body>
</html>