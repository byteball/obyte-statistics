<!DOCTYPE html>
<html>
<head>
<title>Obyte Top 100 richest list</title>
<link rel="stylesheet" type="text/css" href="mystyle.css">
<meta name="Description" CONTENT="Obyte Top 100 richest list">
<meta name="keywords" content="obyte, byteball, witness, hub, relay, statistics" />
<link rel="icon" href="https://obyte.org/static/android-icon-192x192.png">

<script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>
<script type="text/javascript">
	$(document).ready(function(){
		function search(){
			var address=$("#search").val();
			if(address!=""){
				$("#result").html("<img src='ajax-loader.gif'/>");
				$.ajax({
					type:"post",
					url:"findrichest.php",
					data:"address="+address,
					success:function(data){
						$("#result").html(data);
						$("#search").val("");
					}
				});
			}
		}

		$("#button").click(function(){
			search();
		});

		$('#search').keyup(function(e) {
			if(e.keyCode == 13) {
				search();
			}
		});
	});
</script>
<style>
body {
	width: 800px;
}
#search {
	width: 700px;
	padding: 10px;
}
#button {
	display: block;
	width: 100px;
	height: 30px;
	border: solid #366FEB 1px;
	background: #91B2FA;
}
</style>

</head>
<body>

<table>
	<tr>
		<td><a href="https://obyte.org"><img src="https://obyte.org/static/android-icon-192x192.png" height="100" width="100"></a></td>
		<td style="padding-left: 10px"><center><h1>O<sub>byte</sub> Top 100 richest list</h1></center></td>
	</tr>
</table>

<br><br>

<div id="container" style="position: relative">
	<font size="+1">Find yourself among the richest!</font><br>
	<input type="text" id="search" placeholder="Your Obyte address here."/>

	<table>
		<tr>
			<td><input type="button" id="button" value="Search" /></td><td width="10"></td><td id="result" value=""></td>
		</tr>
	</table>
</div>
<br>
<table border="0">
	<tr>
		<td width="50"><b>Rank</b></td>
		<td width="180"><b>Amount (in bytes)</b></td>



<?php
$rate_url="https://api.coinmarketcap.com/v1/ticker/byteball/";

$json_array= json_decode(make_443_get ($rate_url), true);
if(!defined($json_array['0']['price_usd'])){
	$dollar_value=round($json_array['0']['price_usd'],2);
} else {
	$dollar_value="rate missing";
}

echo "
		<td width=\"180\"><b>USD <font size=\"-2\">(at 1GB=$".$dollar_value.")</font></b></td>
		<td width=\"200\"><b><center>Address</center></b></td>
	</tr>
";

//$home_dir = $_SERVER['HOME'];
//if (!$home_dir)
//	$home_dir = $_SERVER['DOCUMENT_ROOT'].'/../..';
$stats_db = new SQLite3('../stats.sqlite', SQLITE3_OPEN_READONLY);
$stats_db->busyTimeout(30*1000);

$query = "SELECT * FROM richlist order by amount DESC LIMIT 100";

$results = $stats_db->query($query);    
if ( ! $results ) {
	echo "Problem here..."; 
	exit;
}
$i=1;
while( $row = $results->fetchArray(SQLITE3_ASSOC) ){
	echo "<tr><td><b>#".$i."</b></td><td>".number_format ($row[ 'amount' ])."</td><td>$".number_format (($row[ 'amount' ]/1000000000)*$dollar_value)."</td><td><a href=\"https://explorer.obyte.org/#".$row[ 'address' ]."\">".$row[ 'address' ]."</a></td></tr><tr>";
	$i++;
}

?>

</table>
<br>
<font size="-1">

#1 MZ4GUQC7WUKZKKLGAS3H3FSDKLHI7HFO holds the remaining disribution amount.<br>
Rate powered by <a href="https://coinmarketcap.com/currencies/byteball/" target="_blank">CoinMarketCap</a><br><br></i>


<?php
function make_443_get ($url) {
	$url=$url;
	$timeout = 10;// Le temps maximum d'exécution de la fonction cURL (en secondes)


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

		return $output;

	} else {

		//echo 'errore here:' . curl_error($ch);

		$buff_code = array('error' => 1, 'error_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE));
		curl_close($ch);
		return json_encode($buff_code); //426

	}

	// close curl resource to free up system resources 
}

include('footer.php');
?>
