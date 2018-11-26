<?php
//This scripts timestamps main chain units based on time interpolation from timestamp Byeball oracle
//and updates the mci_timestamp table.
//Should be run every minute from a cron job.



$time_in = time();

include_once '/var/www/path_to_your_mysql_credentials/mysql.php';
$db = new SQLite3('/root/.config/byteball-hub/byteball.sqlite');


/*
 * where are we ?
 */
 
$q = mysqli_query($mysqli, "select max(main_chain_index) as max_MCI from mci_timestamp" );

$row = mysqli_fetch_assoc ( $q );

$max_MCI = $row[ 'max_MCI' ];

$last_timestamped_mci = $max_MCI;

/*
 * first get timestamp oracle info
 */

$query = "select units.main_chain_index";
$query .= ", data_feeds.int_value";
$query .= " from units";
$query .= " left join unit_authors on unit_authors.unit = units.unit";
$query .= " left join data_feeds on data_feeds.unit = units.unit";
$query .= " where 1";
$query .= " and unit_authors.address='I2ADHGP4HL6J37NQAD73J7E5SKFIXJOT'";
$query .= " and data_feeds.feed_name='timestamp'";
$query .= " and units.main_chain_index > '$last_timestamped_mci' ";
$query .= " order by units.main_chain_index";

$results = $db->query( $query );

while ($row = $results->fetchArray(SQLITE3_ASSOC)) {

	echo "<br>".print_r($row,true);
	echo " " . date( 'Y-m-d H:i:s', round($row[ 'int_value' ]/1000) );

	$query = "insert into mci_timestamp set main_chain_index = '$row[main_chain_index]'";
	$query .= ", date = '" . date( 'Y-m-d H:i:s', round($row[ 'int_value' ]/1000) ) . "'";

	$q = mysqli_query($mysqli, $query);
    
}



/*
 * then complete by filling the holes
 */

$q = mysqli_query($mysqli, "select main_chain_index, unix_timestamp(date) as timestamp from mci_timestamp order by main_chain_index" );


$from_mci = 0;

while( $row = mysqli_fetch_assoc ( $q ) ){

	$to_mci = $row[ 'main_chain_index' ];
	$to_timestamp = $row[ 'timestamp' ];

	if( ! empty( $from_mci ) ){

		interpolate_timestamp( $from_mci, $from_timestamp, $to_mci, $to_timestamp );

	}

	$from_mci = $to_mci;
	$from_timestamp = $to_timestamp;
    

}



function interpolate_timestamp( $from_mci, $from_timestamp, $to_mci, $to_timestamp ){

	global $mysqli;

//     echo "<br>interpolate_timestamp( $from_mci, $from_timestamp, $to_mci, $to_timestamp )";
    
	$delta_time = $to_timestamp-$from_timestamp;
	$delta_mci = $to_mci - $from_mci;
    
//     echo "<br>delta_time: ".$delta_time;
//     echo "<br>delta_mci: ".$delta_mci;

	for( $mci = ($from_mci + 1); $mci<$to_mci; $mci++ ){
    
		$interpolated_time = round( $from_timestamp + ( $mci - $from_mci ) / $delta_mci * $delta_time ) ;
//         echo "<br>interpolated_time of mci $mci : ".date( 'Y-m-d H:i:s', $interpolated_time);
        
		mysqli_query($mysqli, "insert into mci_timestamp set main_chain_index='$mci', date='" . date( 'Y-m-d H:i:s', $interpolated_time) . "'" );
        
    
    }


}
 


$total_time = time() - $time_in;
echo "\n<br><br>done in " . $total_time . " sec";

?>
