Free of copyright

//This script extract some hubs and full wallets peer events from the Byteball sqlite database
//then uses this information to fill the byteball.fr Mysql geomap database
//then dumps a json file that will be queried later by the byteballworldmap.php publis script to render the map.
//This script should be periodically executed in a cron job.
//An api key is required to access to http://api.ipstack.com (free access)

//The geomap table structure is as follow 
/*CREATE TABLE `geomap` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('hub','relay','full_wallet') NOT NULL,
  `IP` varchar(15) NOT NULL,
  `longit` float NOT NULL,
  `latt` float NOT NULL,
  `description` varchar(50) NOT NULL,
  `is_ok` tinyint(1) NOT NULL DEFAULT '1',
  `date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;*/




<?php
include_once '/path_to_your_mysql_credentials/mysql.php';//user and password of our Mysql database to the geomap table
$db = new SQLite3('/root/.config/byteball-hub/byteball.sqlite');
$max_alea=0.025;# in degree, 1/100 deg=1km


#flag everything down in the geomap table
$query = "update geomap set is_ok=0 where 1"; 
	$q = mysqli_query($mysqli, $query);    
	if ( ! $q ) {
		echo "Problem here...";
		echo mysqli_error( $mysqli );
		exit;       
	}

##################pass 1 : search for all active hubs in byteball sqlite database
$results = $db->query( "select * from peer_host_urls where is_active=1 group by url order by creation_date asc" );

if (! $results) {
	echo "<p>There was an error in query: $query</p>";
	echo $db->lastErrorMsg();
	exit;
}

while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
	#Do we know this IP ?
	$query = "select * from geomap where type='hub' and IP LIKE '".$row[ 'peer_host' ]."' and description LIKE '".$row[ 'url' ]."'";
	$q = mysqli_query($mysqli, $query);
	if ( ! $q ) {
		echo mysqli_error( $mysqli );
		exit;
	}
	if(mysqli_num_rows ( $q )==1 && is_hub_listening ($row[ 'url' ])){#already exists
		$query = "update geomap set is_ok=1,date=now() where IP LIKE '".$row[ 'peer_host' ]."' and description LIKE '".$row[ 'url' ]."'";
		//echo $query;
		//echo "\n";
		//echo $row[ 'url' ]." is known and listening\n";
		$q = mysqli_query($mysqli, $query);
		if ( ! $q ) {
			echo mysqli_error( $mysqli );
			exit;
		}
	} else if(is_hub_listening ($row[ 'url' ])) {#insert
		$data_array= json_decode(get_coord($row[ 'peer_host' ]), true);
		$query = "INSERT INTO geomap (type, IP, longit, latt, description, date) VALUES ('hub', '" . $row[ 'peer_host' ] . "', '" . addslashes ($data_array[ 'longitude' ]+insert_alea($max_alea)) . "', '" . addslashes ($data_array[ 'latitude' ]+insert_alea($max_alea)) . "', '" . $row[ 'url' ] . "',now())";
		$q = mysqli_query($mysqli, $query);    
		if ( ! $q ) { 
			echo "Problem here... query insert";
			echo mysqli_error( $mysqli );
			exit;
		}
	} else {
				//echo $row[ 'url' ]." is not listening\n";
	}
}

#adding byteball.org and byteball.fr
$row[ 'peer_host' ]="163.172.89.110";
$query = "select * from geomap where type='hub' and IP='".$row[ 'peer_host' ]."'";
$q = mysqli_query($mysqli, $query);
if ( ! $q ) { 
	echo mysqli_error( $mysqli );
	exit;
}
$row[ 'url' ]="wss://byteball.fr/bb";
if(is_hub_listening ($row[ 'url' ])){
	if(mysqli_num_rows ( $q )<1){
		$data_array= json_decode(get_coord($row[ 'peer_host' ]), true);
		$query = "INSERT INTO geomap (type, IP, longit, latt, description, date) VALUES ('hub', '" . $row[ 'peer_host' ] . "', '" . addslashes ($data_array[ 'longitude' ]+insert_alea($max_alea)) . "', '" . addslashes ($data_array[ 'latitude' ]+insert_alea($max_alea)) . "', '" . $row[ 'url' ] . "',now())";
		$q = mysqli_query($mysqli, $query);    
		if ( ! $q ) {
			echo "argh";
			echo mysqli_error( $mysqli );
			exit;
		}
	} else {
		$query = "update geomap set is_ok=1, date=now() where IP='".$row[ 'peer_host' ]."'";
		$q = mysqli_query($mysqli, $query);
		if ( ! $q ) {
			echo mysqli_error( $mysqli );
			exit;
		}
	}
}

$row[ 'peer_host' ]="144.76.217.155";
$query = "select * from geomap where type='hub' and IP='".$row[ 'peer_host' ]."'";
$q = mysqli_query($mysqli, $query);
if ( ! $q ) { 
	echo mysqli_error( $mysqli );
	exit;
}
$row[ 'url' ]="wss://byteball.org/bb";
if(is_hub_listening ($row[ 'url' ])){
	if(mysqli_num_rows ( $q )<1){

		$data_array= json_decode(get_coord($row[ 'peer_host' ]), true);
		$query = "INSERT INTO geomap (type, IP, longit, latt, description, date) VALUES ('hub', '" . $row[ 'peer_host' ] . "', '" . addslashes ($data_array[ 'longitude' ]+insert_alea($max_alea)) . "', '" . addslashes ($data_array[ 'latitude' ]+insert_alea($max_alea)) . "', '" . $row[ 'url' ] . "',now())";
		$q = mysqli_query($mysqli, $query);    
		if ( ! $q ) { 
			echo mysqli_error( $mysqli );
			exit;
		}
	} else {
		$query = "update geomap set is_ok=1, date=now() where IP='".$row[ 'peer_host' ]."'";
		$q = mysqli_query($mysqli, $query);
		if ( ! $q ) {
			echo mysqli_error( $mysqli );
			exit;
		} 					
	}
}

#erase all failed hubs
$query = "delete from geomap where is_ok=0 and type='hub'"; 
$q = mysqli_query($mysqli, $query);    
if ( ! $q ) {
	echo "Problem here...";
	echo mysqli_error( $mysqli );
	exit;
}


# ******** PASS 2 *************   search for full wallets
# Lord says "peer_events come from full wallets only"
$results = $db->query( "select * from peer_events where (julianday('now') - julianday(event_date))* 24 * 60 * 60  < 3600*24 group by peer_host" );

if (! $results) {
	echo "argh";
	echo $db->lastErrorMsg();
	exit;
}

while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
	if(preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $row[ 'peer_host' ])){
		
		$query = "select * from geomap where IP='".$row[ 'peer_host' ]."' and type <> 'hub'";
		$q = mysqli_query($mysqli, $query);
		if ( ! $q ) {
			echo mysqli_error( $mysqli );
			exit;
		}
		if(mysqli_num_rows ( $q )==1){#if exists

			$query = "update geomap set is_ok=1, date=now() where IP='".$row[ 'peer_host' ]."'";
			$q = mysqli_query($mysqli, $query);
		} else {#insert it if if it is not known as a hub
			$query = "select * from geomap where IP='".$row[ 'peer_host' ]."' and type = 'hub'";
			$q = mysqli_query($mysqli, $query);
			if ( ! $q ) {
				echo mysqli_error( $mysqli );
				exit;
			}
			if(mysqli_num_rows ( $q )==0){
				$data_array= json_decode(get_coord($row[ 'peer_host' ]), true);

				$query = "INSERT INTO geomap (type, IP, longit, latt, description, date) VALUES ('full_wallet', '" . $row[ 'peer_host' ] . "', '" . addslashes ($data_array[ 'longitude' ]+insert_alea($max_alea)) . "', '" . addslashes ($data_array[ 'latitude' ]+insert_alea($max_alea)) . "', '" . "Full wallet" . "',now())";
				$q = mysqli_query($mysqli, $query);    
				if ( ! $q ) {
					echo "Problem here... query insert";
					echo mysqli_error( $mysqli );
					exit;
				}
			}
	   }
	}
}



#erase all not alive previous records (aka is_ok=0) before Json dump
$query = "delete from geomap where is_ok=0"; 
$q = mysqli_query($mysqli, $query);    
if ( ! $q ) {
	echo "Problem here...";
	echo mysqli_error( $mysqli );
	exit;
}


#json Dump

$query = "SELECT * FROM geomap"; 
$q = mysqli_query($mysqli, $query);    
if ( ! $q ) {
	echo "Problem here...";
	echo mysqli_error( $mysqli );
	exit;
}

$hub_result_array=[];
$result_json="";
if(mysqli_num_rows ( $q )>0){
	while( $row = mysqli_fetch_assoc ( $q ) ){
		//echo "id:".$row[ 'id' ]." type:".$row[ 'type' ]." IP:".$row[ 'IP' ]." longit:".$row[ 'longit' ]." latt:".$row[ 'latt' ]." description:".$row[ 'description' ]." <br>";
		if(preg_match('/hub/',$row[ 'type' ])) {
			$buff_description="<b> Hub: ".$row[ 'description' ]."<br>IP: ".$row[ 'IP' ]."</b>";
		} else if(preg_match('/relay/',$row[ 'type' ])) {
			$buff_description="<b> Relay: ".$row[ 'description' ]."<br>IP: ".$row[ 'IP' ]."</b>";

		} else {
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

	$result_json=json_encode($hub_result_array);
	file_put_contents('/var/www/public_html/json/byteball_map.json', $result_json);
							
}else{
	echo "Not found.";
}


//var_dump(json_decode($result_json, true));

function is_hub_listening ($wss_url){
	$url=str_replace('ws','http',$wss_url);
	$return_code=make_443_get($url);
	if($return_code!=426){
		return false;
	} else {
		return true;
	}
}

function make_443_get ($peer_url) {
	$url=$peer_url;
	$timeout = 10;

	// create curl resource 
	$ch = curl_init(); 

	// curl_setopt
	curl_setopt($ch, CURLOPT_URL, $url); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_PORT, 443);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); 
	curl_setopt($ch, CURLOPT_FAILONERROR,true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

	if($output = curl_exec($ch)){ 
		return;
	} else {
		//echo 'errore here:' . curl_error($ch);
		$buff_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return ($buff_code); //426
	}

	// close curl resource to free up system resources 
}

function insert_alea ($my_max_alea){   #randomize a little spots display on the map within a short realistic range
	$return_value =  rand( -$my_max_alea*1000 , $my_max_alea*1000 )/1000;
	return $return_value;
}

function get_coord($IP)
{
	$json = file_get_contents("http://api.ipstack.com/$IP?access_key=xxxxxxxxxxxxx");  //<---- your API key here

	if(!$json) {
		echo "pas pu recuperer coordonnées";
		exit;
	} else {
		return $json;
	}
}



?>
