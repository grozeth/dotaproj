<?php


		if ($resource == 'matches') {
			$data = fetchMatches();
			echo "<table cellpadding='1' cellspacing='1' border='0'>
						<tr>";
			echo "<th>Hero</th>";
			echo "<th>ID</th>";
			echo "<th>Result</th>";
			echo "<th>Duration</th>";
			echo "</tr>";
			foreach ($data['matches'] as $match) {
				echo $match['player_team'] == '1' ? "<tr class='match radiant-bg'>" : "<tr class='match dire-bg'>" ;
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
			
		}
		elseif ($resource == 'fillData') {
			FillDatabase();
		}
		else {
			header('HTTP/1.1 404 Not Found');
			echo '<p><a href="http://localhost/matches/">View last 10 matches</a></p>';
		}

		?>