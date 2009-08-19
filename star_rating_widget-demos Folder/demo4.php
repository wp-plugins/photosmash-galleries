<?php
sleep(2);

// Check, if we need to proccess the FORM submission (or AJAX call that pretends POST method)
if($_SERVER["REQUEST_METHOD"] == 'POST')
{
	// veriffy user input!
	$vote = in_range($_POST['rate'], 1, 5);

	// update statistic and save to file
	$db = save_vote($vote);

	// For AJAX requests we'll return JSON object with current vote statistics
	if($_SERVER['HTTP_X_REQUESTED_WITH'])
	{
		header('Cache-Control: no-cache');
		echo json_encode($db); // requires: PHP >= 5.2.0, PECL json >= 1.2.0
	}
	// For non-AJAX requests we are going to echo {$post_message} variable in main script
	else
	{
		$opt = get_options();
		$post_message = "{$opt[$vote]} ($vote)";
	}
}


// ========================
// Functions
// ========================
function get_options() {
	return array(
		1 => 'Not so great',
		2 => 'Quite good',
		3 => 'Good',
		4 => 'Great!',
		5 => 'Excellent!',
	);
}

function in_range($val, $from=0, $to=100) {
	return min($to, max($from, (int)$val));
}

function get_dbfile() {
	return preg_replace('#\.php$#', '.dat', __FILE__);
}

function get_votes() {
	$dbfile = get_dbfile();
	return is_file($dbfile) ? unserialize(file_get_contents($dbfile)) : array('votes' => 0, 'sum' => 0, 'avg' => 0);
}

function save_vote($vote) {
	$db = get_votes();
	$db['votes']++;
	$db['sum'] += $vote;
	$db['avg'] = sprintf('%01.2f', $db['sum'] / $db['votes']);
	file_put_contents(get_dbfile(), serialize($db));

	return $db;
}
