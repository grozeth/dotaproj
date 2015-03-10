<?php
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
				;
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
	$records = $connection->query("SELECT COUNT(*)as records FROM matches as m, players as p WHERE p.account_id = '".$id."' AND p.match_id = m.match_id");
	$records = (mysqli_fetch_assoc($records));
	$offset = $records['records'] - $start_point;
	if ($offset < 0) {
		$offset = 0;
		$limit = $records['records']-($start_point-10);
	}
	$query = "SELECT m.match_id, m.duration, m.radiant_win, m.game_mode, p.account_id FROM matches as m, players as p WHERE p.account_id = '".$id."' AND p.match_id = m.match_id ORDER BY m.match_id LIMIT ".$offset.", ".$limit; 
	
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
	$teamTotal['level'] = 0;
	$teamTotal['kills'] = 0;
	$teamTotal['deaths'] = 0;
	$teamTotal['assists'] = 0;
	$teamTotal['last_hits'] = 0;
	$teamTotal['denies'] = 0;
	$teamTotal['gold_per_min'] = 0;
	$teamTotal['xp_per_min'] = 0;
	$teamTotal['hero_damage'] = 0;
	$teamTotal['tower_damage'] = 0;
	$teamTotal['hero_healing'] = 0;
	
	echo '<table cellpadding="1" cellspacing="1" border="0" class="team-details">';
	echo '<tr>
			<th>Hero</th>
			<th>Level</th>
			<th>K</th>
			<th>D</th>
			<th>A</th>
			<th>KDA</th>
			<th>LH</th>
			<th>DN</th>
			<th>GPM</th>
			<th>XPM</th>
			<th>HD</th>
			<th>TD</th>
			<th>HH</th>
		</tr>';
			
	foreach ($match['players'] as $player) {
		if (in_array($player['player_slot'], $slots)) {
			echo '<tr><td>';
			{ 
				$me = ($id == $player['account_id']) ? true : false;	
					if ($me) {
						echo '<b>';
					}
					echo parseHeroName($player['hero_id']);
					if ($me) {
						echo '</b>';
					}
			}
			echo '<td>'.$player['level'].'</td>';
			echo '<td>'.$player['kills'].'</td>';
			echo '<td>'.$player['deaths'].'</td>';
			echo '<td>'.$player['assists'].'</td>';
			
			$player_kda_death = $player['deaths'] == 0 ? 1 : $player['deaths'];
			$player_kda = ($player['kills'] + $player['assists']) / $player_kda_death;
			echo '<td>'.round($player_kda, 2).'</td>';
			echo '<td>'.$player['last_hits'].'</td>';
			echo '<td>'.$player['denies'].'</td>';
			echo '<td>'.$player['gold_per_min'].'</td>';
			echo '<td>'.$player['xp_per_min'].'</td>';
			echo '<td>'.$player['hero_damage'].'</td>';
			echo '<td>'.$player['tower_damage'].'</td>';
			echo '<td>'.$player['hero_healing'].'</td>';
			echo '</tr>';
			$teamTotal['level'] += $player['level'];
			$teamTotal['kills'] += $player['kills'];
			$teamTotal['deaths'] += $player['deaths'];
			$teamTotal['assists'] += $player['assists'];
			$teamTotal['last_hits'] += $player['last_hits'];
			$teamTotal['denies'] += $player['denies'];
			$teamTotal['gold_per_min'] += $player['gold_per_min'];
			$teamTotal['xp_per_min'] += $player['xp_per_min'];
			$teamTotal['hero_damage'] += $player['hero_damage'];
			$teamTotal['tower_damage'] += $player['tower_damage'];
			$teamTotal['hero_healing'] += $player['hero_healing'];
		}
	}
	echo '<tr><td colspan="13" class="fill"></td></tr>';
	echo '<tr><td>Total</td>';
	echo '<td>'.($teamTotal['level'] / 5).'</td>';
	echo '<td>'.$teamTotal['kills'].'</td>';
	echo '<td>'.$teamTotal['deaths'].'</td>';
	echo '<td>'.$teamTotal['assists'].'</td>';
	$team_kda_death = $teamTotal['deaths'] == 0 ? 1 : $teamTotal['deaths'];
	$team_kda = ($teamTotal['kills'] + $teamTotal['assists']) / $team_kda_death;
	echo '<td>'.round($team_kda, 2).'</td>';
	echo '<td>'.$teamTotal['last_hits'].'</td>';
	echo '<td>'.$teamTotal['denies'].'</td>';
	echo '<td>'.($teamTotal['gold_per_min'] / 5).'</td>';
	echo '<td>'.($teamTotal['xp_per_min']).'</td>';
	echo '<td>'.$teamTotal['hero_damage'].'</td>';
	echo '<td>'.$teamTotal['tower_damage'].'</td>';
	echo '<td>'.$teamTotal['hero_healing'].'</td>';
	echo '</tr>';
	echo '</table>';
} 

function parseHeroName ($hero_id) {
	global $herolist;
	return $herolist[$hero_id];
}
?>