<?php 
$match = fetchMatchData($paths[0]);
echo "<div class='match'>";
	echo "<p>Match ID: <a href='http://www.dotabuff.com/matches/".$match['match_id']."' target='_blank'>".$match['match_id']."</a></p>";
	echo ($match['radiant_win'] == 0) ? "<h1 class='dire'>Dire victory!</h1>" : "<h1 class='radiant'>Radiant victory!</h1>";
	echo "<div class='team radiant-bg'>
			<h3>Radiant</h3>";
		parseTeams($match, "Radiant");
	echo "</div>";
	echo "<div class='team dire-bg'>
			<h3>Dire</h3>";
		parseTeams($match, "Dire");
	echo "</div>";
	echo "</div>";
?>