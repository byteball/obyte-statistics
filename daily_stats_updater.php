<?php

//This script should be ran from a periodic cron job once a day.
//Fills the table daily_stat then dumps daily_stats.json

date_default_timezone_set('UTC');

$time_in = time();
echo "\n<br>script lauched at " . date('Y-m-d H:i:s') . "\n";

$db = new SQLite3($_SERVER['HOME'].'/.config/byteball-hub/byteball.sqlite');
$db->busyTimeout(30*1000);
$db->exec("PRAGMA foreign_keys = 1");
$db->exec("PRAGMA journal_mode=WAL");
$db->exec("PRAGMA synchronous=FULL");
$db->exec("PRAGMA temp_store=MEMORY");

$stats_db = new SQLite3('stats.sqlite');
$stats_db->busyTimeout(30*1000);
$stats_db->exec("PRAGMA foreign_keys = 1");
//$stats_db->exec("PRAGMA journal_mode=WAL");
$stats_db->exec("PRAGMA synchronous=FULL");
$stats_db->exec("PRAGMA temp_store=MEMORY");



/*
 * create witnesses_tmp
 */

$query = "CREATE TEMPORARY TABLE witnesses_tmp";
$query .= " ( ";
$query .= " address VARCHAR(32) NOT NULL PRIMARY KEY )";

$results = $db->query( $query );

if (! $results) {
	echo "<p>There was an error in query: $query</p>";
	echo $db->lastErrorMsg();
	exit;
}


/*
 * fill witnesses_tmp
 */
 
$results = $db->query( "select DISTINCT address from unit_witnesses" );

if (! $results) {
	echo "<p>There was an error in query: $query</p>";
	echo $db->lastErrorMsg();
	exit;
}

while ($row = $results->fetchArray(SQLITE3_ASSOC)) {

//     echo "\n<br>" . print_r($row, true);
	$db->query( "insert into witnesses_tmp (address) VALUES ('" . $row[ 'address' ] . "')" );

}

// die('ok');


/*
 * where are we ?
 */


// 

$results = $stats_db->query("select max( main_chain_index ) as max_MCI from mci_timestamps where date( date ) = date( (select max( day ) from daily_stats) )" );

if ( ! $results ) {
	 
	die("erreur : " .  $stats_db->lastErrorMsg());
	
}

$row = $results->fetchArray(SQLITE3_ASSOC);

$max_MCI = $row[ 'max_MCI' ] ? $row[ 'max_MCI' ] : 0; 
echo "max mci ".$max_MCI."\n";



/*
 * create sqlite mci_timestamps_tmp
 */

$query = "CREATE TEMPORARY TABLE mci_timestamps_tmp";
$query .= " ( ";
$query .= " main_chain_index INT UNSIGNED NOT NULL PRIMARY KEY,";
$query .= " date TIMESTAMP NOT NULL )";

$results = $db->query( $query );

if (! $results) {
	echo "<p>There was an error in query: $query</p>";
	echo $db->lastErrorMsg();
	exit;
}


/*
 * fill sqlite mci_timestamps_tmp from the full db
 */
 
$results2 = $stats_db->query("select * from mci_timestamps where main_chain_index > '$max_MCI' order by main_chain_index" );

$db->exec("BEGIN");

while( $row = $results2->fetchArray(SQLITE3_ASSOC) ){

	$query =  "insert into mci_timestamps_tmp (main_chain_index, date) VALUES ('" . $row[ 'main_chain_index' ] . "', '" . $row[ 'date' ] . "' )";

	$results = $db->query( $query );
	
	if (! $results) {
		echo "<p>There was an error in query: $query</p>";
		echo $db->lastErrorMsg();
		exit;
	}

}

$db->exec("COMMIT");


/*
 * counting query
 */
 
$query = "select count(distinct units.unit) as units_count";
$query .= ", count( distinct (CASE WHEN witnesses_tmp.address is NULL THEN units.unit ELSE NULL END) ) as units_nw_count";
$query .= ", count( distinct (CASE WHEN witnesses_tmp.address is NOT NULL THEN units.unit ELSE NULL END) ) as units_w_count";
$query .= ", SUM(CASE WHEN witnesses_tmp.address is NULL THEN units.payload_commission ELSE 0 END) as payload_nw";
$query .= ", SUM(CASE WHEN witnesses_tmp.address is NOT NULL THEN units.payload_commission ELSE 0 END) as payload_w";
$query .= ", SUM( units.payload_commission ) as payload_total";
$query .= ", count( distinct ( CASE WHEN units.is_on_main_chain = '0' THEN units.unit ELSE 0 END ) ) as sidechain_units";
$query .= ", count( distinct unit_authors.address ) as authors";
$query .= ", count( distinct unit_authors.definition_chash ) as new_authors";
$query .= ", date(mci_timestamps_tmp.date) as day";
$query .= " from units";
$query .= " left join unit_authors on unit_authors.unit = units.unit";// va compter plusieurs fois les units multisig ce qui va fausser le comptage des payload
$query .= " left join witnesses_tmp on witnesses_tmp.address = unit_authors.address";
$query .= " left join mci_timestamps_tmp on mci_timestamps_tmp.main_chain_index = units.main_chain_index";
$query .= " where 1";
$query .= " and units.main_chain_index > '$max_MCI'";
$query .= " and date(mci_timestamps_tmp.date) < date('now') ";
$query .= " group by date(mci_timestamps_tmp.date)";
$query .= " order by units.main_chain_index";
// $query .= " limit 0,100";

$results = $db->query( $query );

if (! $results) {
	echo "<p>There was an error in query: $query</p>";
	echo $db->lastErrorMsg();
	exit;
}

$stats_db->exec("BEGIN");

while ($row = $results->fetchArray(SQLITE3_ASSOC)) {

//	echo "\n<br>" . print_r($row, true);
	
	$query = "insert into daily_stats (day, units_w, units_nw, payload_nw, payload_w, sidechain_units, addresses, new_addresses) VALUES ('" . $row[ 'day' ] . "', '" . $row[ 'units_w_count' ] . "', '" . $row[ 'units_nw_count' ] . "', '" . $row[ 'payload_nw' ] . "', '" . $row[ 'payload_w' ] . "', '" . $row[ 'sidechain_units' ] . "', '" . $row[ 'authors' ] . "', '" . $row[ 'new_authors' ] . "')";
	
	$stats_db->query($query );
}

$stats_db->exec("COMMIT");


/*
 * make json
 */
 
$res = array();
 
$results = $stats_db->query("select strftime('%s', day)*1000 as t, units_w, units_nw, payload_nw, payload_w, round(1.0*sidechain_units/(units_w+units_nw)*100) as sidechain_units, addresses, new_addresses from daily_stats order by day" );

while( $row = $results->fetchArray(SQLITE3_ASSOC) ){

	$res[] = $row;

}

$json = json_encode( $res, JSON_NUMERIC_CHECK );

file_put_contents('www/daily_stats.json', $json);
 


$total_time = time() - $time_in;
echo "\n<br><br>done in " . $total_time . " sec\n";

?>
