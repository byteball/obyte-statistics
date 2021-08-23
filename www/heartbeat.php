<?php

include_once 'chart-functions.php';

?><!DOCTYPE html>
<html>
<head>
<title>Obyte HeartBeat</title>
<link rel="stylesheet" type="text/css" href="mystyle.css?v3">
<meta name="Description" CONTENT="obyte stats">
<meta name="keywords" content="obyte, order provider, hub, relay, statistics" />

<link rel="shortcut icon" href="/favicon.ico">
<link rel="icon" type="image/png" sizes="192x192"  href="/android-icon-192x192.png">


<table>
<tr>
<td><a href="/"><img src="/android-icon-192x192.png" height="100" width="100"></a><img src="HeartBeat.png" height="100" width="100"></td>

	<td><center><h1>O<sub>byte</sub> HeartBeat</h1></center></td>

</tr>
</table>

<br><br>


<?php
$stats_db = new SQLite3('../stats.sqlite', SQLITE3_OPEN_READONLY);
$stats_db->busyTimeout(30*1000);

$query = "SELECT * FROM bb_stats order by id DESC LIMIT 1";

$results = $stats_db->query($query);
if ( ! $results ) {
	echo "Problem here...";
	exit;
}

$row = $results->fetchArray(SQLITE3_ASSOC);

if ($row) {

echo "
</center>
<table border=\"0\">
<tr>
<td></td>
<td><center><h2>Current 12 hours snapshot:</h2></center></td>
</tr>
</table>


<table border=\"0\">
	<tr>
		<td width=\"300\"><b>Total active Order Providers</b></td><td><a href=\"/witnesses.php\">".$row[ 'total_active_witnesses' ]."</a></td><td width=\"10\"></td><td></td>
	</tr>
	<tr>
		<td width=\"250\"><b>Total units posted</b></td><td>".$row[ 'total_units' ]."</td><td width=\"10\"></td><td></td>
	</tr>
	<tr>
		<td width=\"250\"><b>Total stable units</b></td><td>".$row[ 'total_stable_units' ]."</font></td><td></td><td></td>
	</tr>
	<tr>
		<td width=\"250\"><b>Total users units </b><font size=\"-2\">(OP and AA posts excluded)</font></td><td>".$row[ 'total_units_witnesses_excluded' ]."</td><td></td><td></td>
	</tr>
	<tr>
	<td width=\"250\"><b>Multisigned addresses units</font></b></td><td>".$row[ 'multisigned_units' ]."</td><td></td><td></td>
	</tr>
	<tr>
			<td width=\"250\"><b>Smart Contract units</b></td><td>".$row[ 'smart_contract_units' ]."</td><td></td><td></td>
	</tr>
	<tr>
			<td width=\"250\"><b>Autonomous Agent units</b></td><td>".($row[ 'total_units' ]-$row[ 'total_units_witnesses_excluded' ])."</td><td></td><td></td>
	</tr>
	<tr>
			<td width=\"250\"><b>Total users payload <font size=\"-2\">(in bytes)</font></b></td><td>".number_format ( $row[ 'total_payload' ] , 0 , "." , "," )."</td><td></td><td></td>
	</tr>
	<tr>
		<td width=\"250\"><b>Sliding stability ratio</b></td><td>".$row[ 'stable_ratio' ]."%</font></td><td></td><td></td>
	</tr>
</table><br>
<i>Updated hourly. Last update: ".$row[ 'UTC_datetime' ]." UTC<br>
<br><br></i>
";



}else{
	echo "Not found.";
}


?>




<table>
<tr>
<td></td>
<td><center><h2>Recent trend: 12 hours sliding window snapshots history</h2></center></td>
</tr>
</table>

<script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>
<script src="https://code.highcharts.com/stock/highstock.js"></script>
<script src="https://code.highcharts.com/stock/modules/exporting.js"></script>


<?php


$params = array(
	array(
		'name' => 'Total units',
		'json_id' => 'a',
	),
	array(
		'name' => 'Total stable units',
		'json_id' => 'b',
	),
	array(
		'name' => 'Excluding OP and AA units',
		'json_id' => 'd',
	),
	array(
		'name' => 'Multisigned units',
		'json_id' => 'e',
	),
	array(
		'name' => 'Smart Contract units',
		'json_id' => 'f',
	),
	array(
		'name' => 'Users payload',
		'json_id' => 'g',
	),
	array(
		'name' => 'Sliding stability ratio',
		'json_id' => 'c',
	)
);

$args = array(
	'title' => 'Recent Obyte trend',
	'subtitle' => '12 hours sliding window snapshots - updated hourly',
	'container_id' => 'container_units_2',
	'params' => $params,
	'json' => 'bb_stats.json',
	'tooltip_pointFormat' => '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}</b> ({point.change}%)<br/>',
	'tooltip_valueDecimals' => 0,
	'tooltip_split' => 'false',
	'plotOptions_compare' => 'percent',
);




show_chart( $args );

?>
<br>
<table>
<tr>
<td></td>
<td><center><h2>Historical daily data:</h2></center></td>
</tr>
</table>

<?php

/*
 * units
 */

$params = array(
	array(
		'name' => 'by others',
		'json_id' => 'units_nw',
	),
	array(
		'name' => 'by Order Providers',
		'json_id' => 'units_w',
	),
);

$args = array(
	'title' => 'Units',
	'subtitle' => 'Posted units - updated daily',
	'container_id' => 'container_units',
	'params' => $params,
	'json' => 'daily_stats.json',
	'tooltip_pointFormat' => '<span style="color:{series.color}">{series.name}</span>: <b>{point.y} units</b><br/>',
	'tooltip_valueDecimals' => 0,
	'tooltip_split' => 'true',
	'plotOptions_compare' => '',
);

show_chart( $args );


/*
 * payload
 */

$params = array(
	array(
		'name' => 'by others',
		'json_id' => 'payload_nw',
	),
	array(
		'name' => 'by Order Providers',
		'json_id' => 'payload_w',
	),
);

$args = array(
	'title' => 'Payload',
	'subtitle' => 'Posted load (in bytes) - updated daily',
	'container_id' => 'container_payload',
	'params' => $params,
	'json' => 'daily_stats.json',
	'tooltip_pointFormat' => '<span style="color:{series.color}">{series.name}</span>: <b>{point.y} bytes</b><br/>',
	'tooltip_valueDecimals' => 0,
	'tooltip_split' => 'true',
	'plotOptions_compare' => '',
);

show_chart( $args );


/*
 * sidechain
 */

$params = array(
	array(
		'name' => 'side chain units rate',
		'json_id' => 'sidechain_units',
	),
);

$args = array(
	'title' => 'Side chain rate',
	'subtitle' => 'Percent of units out of the main chain - updated daily',
	'container_id' => 'container_sc_units',
	'params' => $params,
	'json' => 'daily_stats.json',
	'tooltip_pointFormat' => '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}% of total units</b><br/>',
	'tooltip_valueDecimals' => 0,
	'tooltip_split' => 'false',
	'plotOptions_compare' => '',
);

show_chart( $args );



/*
 * addresses
 */

$params = array(
	array(
		'name' => 'Total addresses',
		'json_id' => 'addresses',
	),
	array(
		'name' => 'New addresses',
		'json_id' => 'new_addresses',
	),
);

$args = array(
	'title' => 'Addresses',
	'subtitle' => 'Unique addresses proportion (new addresses vs total addresses) - updated daily',
	'container_id' => 'container_address',
	'params' => $params,
	'json' => 'daily_stats.json',
	'tooltip_pointFormat' => '<span style="color:{series.color}">{series.name}</span>: <b>{point.y} addresses</b><br/>',
	'tooltip_valueDecimals' => 0,
	'tooltip_split' => 'true',
	'plotOptions_compare' => '',
);

show_chart( $args );

?>

<font size="-1">

<br><br>
<?php include 'footer.php';
