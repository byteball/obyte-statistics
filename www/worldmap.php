<!DOCTYPE html>
<html>
<head>
<title>Obyte world map</title>
<link rel="stylesheet" type="text/css" href="mystyle.css">
<meta name="Description" CONTENT="Obyte world map">
<meta name="keywords" content="obyte, byteball, witness, hub, relay, statistics, map" />
<link rel="icon" href="https://obyte.org/static/android-icon-192x192.png">

<style>
	#page-wrap { width: 800px; margin: 15px auto; position: relative; }
	p { margin: 0 0 15px 0; }
	p:first-child { background: #fffcde; padding: 10px; }
	#sidebar ul { background: #eee; padding: 10px; }
	li { margin: 0 0 0 20px; }
	#main { width: 800px; float: left; }
	#sidebar { width: 190px; position: fixed; left: 78%; top: 100px; margin: 0 0 0 110px; }
	body {
		width: 80em;
		margin: 0 auto;
		font-family: Tahoma, Verdana, Arial, sans-serif;
		font-size: 13px;
		color: #333333;
	}
</style>
<style id="jsbin-css">
/* example style */

.geo-label {
	margin: -2em 0 0 1em;
	padding: .25em .5em;
	font-family: Arial;
	font-size: .7em;
	color: #444;
	background: #ffffff;
	border-radius: 0px;
	border: solid 1px #444;
	width: auto;

	/* we will show it on geomapmove */
	display: none;
}

/* template style */

html {
	font-family: Tahoma, Verdana, Arial, sans-serif;
	font-size: 100%;
}

#map {
	position: relative;
	top: 0em;
	right: 0;
	bottom: 0;
	left: 0;
}

#info {
	background: #f0f0f0;
	border-radius: .0em;
	box-shadow: .2em .2em .4em #222;
	font-size: 1em;
	max-width: 66%;
	padding: .25em .5em;
	position: absolute;
	left: 1em;
	top: 1em;
}

</style>
</head>
<body>

	
	<div id="ie6-wrap">

	<div id="page-wrap">
	
		
		<div id="main">
			<table>
				<tr>
					<td><a href="https://obyte.org"><img src="https://obyte.org/static/android-icon-192x192.png" height="100" width="100"></a></td>
					<td><center><h1>Obyte World map</h1></center></td>
				</tr>
				<tr>
					<td></td><td></td>
				</tr>
			</table>


<br><br>

<?php
	
//	$home_dir = $_SERVER['HOME'];
//	if (!$home_dir)
//		$home_dir = $_SERVER['DOCUMENT_ROOT'].'/../..';
	$stats_db = new SQLite3('../stats.sqlite', SQLITE3_OPEN_READONLY);
	$stats_db->busyTimeout(30*1000);

	$query = "select count(*) as total_count from geomap where type='hub'";
	$results = $stats_db->query($query);
	if ( ! $results ) {
		echo $stats_db->lastErrorMsg();
		exit;       
	}
	while( $row = $results->fetchArray(SQLITE3_ASSOC) ){
		$hub_numbers=$row[ 'total_count' ];
	}

	$query = "select count(*) as total_count from geomap where type='full_wallet'";
	$results = $stats_db->query($query);
	if ( ! $results ) {
		echo $stats_db->lastErrorMsg();
		exit;       
	}
	while( $row = $results->fetchArray(SQLITE3_ASSOC) ){
		$full_wallets_numbers=$row[ 'total_count' ];
	}
	
	$query = "select date from geomap order by date desc limit 1";
	$results = $stats_db->query($query);
	if ( ! $results ) { 
		echo $stats_db->lastErrorMsg();
		exit;       
	}
	while( $row = $results->fetchArray(SQLITE3_ASSOC) ){
		$mytime= $row[ 'date' ];
	}
 


echo "<div id=\"map\" style=\"height: 400px; min-width: 310px; width: 100%\">

  <div id=\"info\">
  <table>
	<tr>
		<td><img src=\"https://obyte.org/static/android-icon-192x192.png\" height=\"25\" width=\"25\"></td><td width=\"5\"></td><td><font size=\"+1\">Hubs/relays and full wallets</font></td>
	</tr>
	<tr>
		<td></td><td></td><td><font size=\"-1\"><b>".$hub_numbers."</b> hubs/relays and <b>".$full_wallets_numbers."</b> full wallets counted today</font></td>
	</tr>
	<tr>
	<td></td><td></td><td><font size=\"-1\"><i>Last update: ".$mytime." UTC+2</i></font></td>
	</tr>
  </table>
	</div>
  </div>
";
?>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script src="/js/jquery.geo.min.js"></script>

<script>
$(function() {

	var jsondata;
	jQuery.get('/obyte_map.json', function(data) {
		jsondata=data;


		var map = $("#map").geomap( {
			center: [ 0.00, 26.00 ],
			zoom: 2,
			zoomMin: 2,
			zoomMax: 12,
			shapeStyle: {
				color: "#006400",
				width: 16,
				height: 8
			},
			move: function( e, geo ) {
				var monument = map.geomap("find", geo, 6);
				var monument2 = map.geomap("find", geo, 1000);
				$(".geo-label").hide();
				if ( monument.length > 0 ) {
					$(".geo-label").hide();
					var found=0;
					for (var i = 0; i < monument.length; i++) {
						if(monument[i].properties.name.match(/obyte\.org/g)){
							found=1;
							$("." + monument2[i].properties.id).closest(".geo-label").show();
						}
					}
				   if(found==0){
					   $("." + monument[0].properties.id).closest(".geo-label").show();
				   } else {
						$(".geo-label").hide();
						for (var i = 0; i < monument2.length; i++) {
							if(monument2[i].properties.name.match(/obyte\.org/g)){
								found=1;
								$("." + monument2[i].properties.id).closest(".geo-label").show();
							}
						}       	   	
				   }
				} else {
					$(".geo-label").hide();
				}
			}
		} );




		var monuments = jsondata;


		$.each( monuments, function() {

			if (this.properties.name.match(/Hub/g) && !this.properties.name.match(/obyte\.org\/bb/g) ){
				map.geomap("append", this, { color: "#006400", fillOpacity: "0",height:8,width: 16 }, '<span class="' + this.properties.id + '">' +  this.properties.name + '</span>', false);
			} else if(this.properties.name.match(/obyte\.org\/bb/)){//"#006400"
				var buff="<table><tr><td><img src=\"https://obyte.org/static/android-icon-192x192.png\" width=\"30\" height=\"30\"></td><td width=\"5\"></td><td><b>Default Hub: obyte.org/bb<br>IP: 144.76.217.155</b></td></tr></table>";
				map.geomap("append", this, { color: "#006400", strokeWidth: "3px", fillOpacity: "0",height:10,width: 20 }, '<span class="' + this.properties.id + '">' +  buff + '</span>', false);
			}
			else if (this.properties.name.match((/Full/g))){
				map.geomap("append", this, { color: "#1560bd", strokeWidth: "5px", opacity: "0.8", fillOpacity: "0",height:5, width: 5, }, '<span class="' + this.properties.id + '">' +  this.properties.name + '</span>', false);
			}

		} );


		map.geomap("refresh");

	});

});

</script>

<br>
<font size="-1">
<i>Updated hourly.<br><br></i>
</font>
<center>
<?php include('footer.php'); ?>
