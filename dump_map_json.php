<?php
// Free of copyright

//This script extract some hubs and full wallets peer events from the Obyte sqlite database
//then uses this information to fill the sql geomap database
//then dumps a json file that will be queried later by the worldmap.php public script to render the map.
//This script should be periodically executed in a cron job.
//An api key is required to access to http://api.ipstack.com (free access)

include_once('conf.php');
$db = new SQLite3($_SERVER['HOME'].'/.config/obyte-hub/byteball.sqlite');
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


$max_alea=0.025;# in degree, 1/100 deg=1km


#flag everything down in the geomap table
$query = "update geomap set is_ok=0 where 1"; 
$results = $stats_db->query($query);    
if ( ! $results ) {
	echo "Problem here...";
	echo $stats_db->lastErrorMsg();
	exit;       
}

$known_peers = [];
$known_peers['wss://obyte.org/bb'] = false;
$known_peers['wss://relay.papabyte.com/bb'] = false;
$known_peers['wss://obyte-hub.com/bb'] = false;
$known_peers['wss://hub.byteball.ee'] = false;
$known_peers['wss://hub.obytechina.org/bb'] = false;
$known_peers['wss://relay.bytes.cash/bb'] = false;
$known_peers['wss://hub.connectory.io/bb'] = false;

##################pass 1 : search for all active hubs in byteball sqlite database
$results = $db->query( "select peer AS url, peer_host from peers" );

if (! $results) {
	echo "<p>There was an error in query: $query</p>";
	echo $db->lastErrorMsg();
	exit;
}

while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
	$known_peers[$row['url']] = $row[ 'peer_host' ];
}

foreach ($known_peers as $peer_url => $old_host) {
	$new_host = is_peer_listening($peer_url);
	if ( !$new_host ) {
		continue;
	}
	$peer_type = get_peer_type($peer_url);
	$query = sprintf('select * from geomap where type="%s" and IP = "%s" and description = "%s";', $peer_type, $old_host ? $old_host : $new_host, $peer_url);
	$results = $stats_db->query($query);
	if ( !$results ) { 
		die($stats_db->lastErrorMsg());
	}
	if( !$results->fetchArray(SQLITE3_ASSOC) ){
		$data_array = json_decode(get_coord($new_host), true);
		$query = sprintf('INSERT INTO geomap (`type`, `IP`, `longit`, `latt`, `description`) VALUES ("%s", "%s", "%s", "%s", "%s");', $peer_type, $new_host, addslashes($data_array[ 'longitude' ]+insert_alea($max_alea)), addslashes($data_array[ 'latitude' ]+insert_alea($max_alea)), $peer_url);
		$results = $stats_db->query($query);
		if ( !$results ) { 
			die($stats_db->lastErrorMsg());
		}
	}
	else {
		$query = sprintf('update geomap set is_ok=1, date=datetime("now"), IP = "%s" where IP="%s";', $new_host, $old_host ? $old_host : $new_host);
		$results = $stats_db->query($query);
		if ( !$results ) {
			die($stats_db->lastErrorMsg());
		}
	}
}

#erase all failed hubs
$query = "delete from geomap where is_ok=0 and type IN ('hub', 'relay')"; 
$results = $stats_db->query($query);    
if ( ! $results ) {
	echo "Problem here...";
	echo $stats_db->lastErrorMsg();
	exit;
}


# ******** PASS 2 *************   search for full wallets
# Lord says "peer_events come from full wallets only"
$results = $db->query( "select * from peer_events where event_date > datetime('now', '-1 DAY') group by peer_host" );

if (! $results) {
	echo "argh";
	echo $db->lastErrorMsg();
	exit;
}

while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
	if(preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $row[ 'peer_host' ])){
		
		$query = "select * from geomap where IP='".$row[ 'peer_host' ]."' and type NOT IN ('hub', 'relay')";
		$results2 = $stats_db->query($query);
		if ( ! $results2 ) {
			echo $stats_db->lastErrorMsg();
			exit;
		}
		if($results2->fetchArray(SQLITE3_ASSOC)){#if exists

			$query = "update geomap set is_ok=1, date=datetime('now') where IP='".$row[ 'peer_host' ]."'";
			$results2 = $stats_db->query($query);
		} else {#insert it if it is not known as a hub
			$query = "select * from geomap where IP='".$row[ 'peer_host' ]."' and type IN ('hub', 'relay')";
			$results2 = $stats_db->query($query);
			if ( ! $results2 ) {
				echo $stats_db->lastErrorMsg();
				exit;
			}
			if(!$results2->fetchArray(SQLITE3_ASSOC)){
				$data_array= json_decode(get_coord($row[ 'peer_host' ]), true);

				$query = "INSERT INTO geomap (type, IP, longit, latt, description) VALUES ('full_wallet', '" . $row[ 'peer_host' ] . "', '" . addslashes ($data_array[ 'longitude' ]+insert_alea($max_alea)) . "', '" . addslashes ($data_array[ 'latitude' ]+insert_alea($max_alea)) . "', '" . "Full wallet" . "')";
				$results2 = $stats_db->query($query);
				if ( ! $results2 ) {
					echo "Problem here... query insert";
					echo $stats_db->lastErrorMsg();
					exit;
				}
			}
	   }
	}
}



#erase all not alive previous records (aka is_ok=0) before Json dump
$query = "delete from geomap where is_ok=0"; 
$results = $stats_db->query($query);
if ( ! $results ) {
	echo "Problem here...";
	echo $stats_db->lastErrorMsg();
	exit;
}


#json Dump

$query = "SELECT * FROM geomap"; 
$results = $stats_db->query($query);
if ( ! $results ) {
	echo "Problem here...";
	echo $stats_db->lastErrorMsg();
	exit;
}

$hub_result_array=[];
$result_json="";
while( $row = $results->fetchArray(SQLITE3_ASSOC) ){
	//echo "id:".$row[ 'id' ]." type:".$row[ 'type' ]." IP:".$row[ 'IP' ]." longit:".$row[ 'longit' ]." latt:".$row[ 'latt' ]." description:".$row[ 'description' ]." <br>";
	if($row[ 'type' ] === 'hub') {
		$buff_description="<b> Hub: ".$row[ 'description' ]."<br>IP: ".$row[ 'IP' ]."</b>";
	}
	else if($row[ 'type' ] === 'relay') {
		$buff_description="<b> Relay: ".$row[ 'description' ]."<br>IP: ".$row[ 'IP' ]."</b>";
	}
	else {
		//$buff_description="<b>".$row[ 'description' ]."<br>IP: ".$row[ 'IP' ]."</b>";
		$buff_description="<b>".$row[ 'description' ]."</b>";
	}
	$buff_hub_result=array(
		"type" => "Feature",
		"geometry" => array(
			"type" => "Point",
			"coordinates" => array($row[ 'longit' ],$row[ 'latt' ]),
		),
		"properties" =>array(
			"id"=>$row[ 'id' ],
			"name"=>$buff_description,
		),
	);

	array_push($hub_result_array,$buff_hub_result);
}

if($hub_result_array){
	$result_json=json_encode($hub_result_array);
	file_put_contents('www/obyte_map.json', $result_json);
							
}else{
	echo "Not found.";
}

function get_peer_type($wss_url){
	if ($wss_url === 'wss://byteball.org/bb' ||
		$wss_url === 'wss://byteball.fr/bb' ||
		$wss_url === 'wss://obyte.org/bb' ||
		(strpos($wss_url, 'hub') !== false && strpos($wss_url, 'relay') === false)) {
		return 'hub';
	}
	return 'relay';
}

function is_peer_listening($wss_url){
	$url=str_replace('ws','http',$wss_url);
	$result=make_443_get($url);
	if(!empty($result['http_code']) && $result['http_code'] == 426){
		return !empty($result['ip_address']) ? $result['ip_address'] : false;
	}
}

function make_443_get($peer_url) {
	// create curl resource 
	$ch = curl_init(); 

	// curl_setopt
	curl_setopt($ch, CURLOPT_URL, $peer_url); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_PORT, 443);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

	$output = curl_exec($ch);
	//echo 'errore here:' . curl_error($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$ip_address = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
	// close curl resource to free up system resources 
	curl_close($ch);

	return ['output'=> $output, 'http_code' => $http_code, 'ip_address' => $ip_address];
}

function insert_alea($my_max_alea){   #randomize a little spots display on the map within a short realistic range
	$return_value =  rand( -$my_max_alea*1000 , $my_max_alea*1000 )/1000;
	return $return_value;
}

function get_coord($IP)
{
	global $IPSTACK_API_KEY;

	if ($IPSTACK_API_KEY) {
		$json = file_get_contents("http://api.ipstack.com/$IP?access_key=$IPSTACK_API_KEY");  //<---- your API key here
		if($json) {
			return $json;
		}
	}
	else {
		return '{"latitude":0,"longitude":0}';
	}

	die("error in get_coord function");
}
