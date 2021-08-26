<!DOCTYPE html>
<html>
<head>
<title>Obyte Top 100 richest list</title>
<link rel="stylesheet" type="text/css" href="mystyle.css?v3">
<meta name="Description" CONTENT="Obyte Top 100 richest list">

<meta name="keywords" content="obyte, witness, hub, relay, statistics" />

<link rel="shortcut icon" href="/favicon.ico">
<link rel="icon" type="image/png" sizes="192x192"  href="/android-icon-192x192.png">

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


</head>
<body class="richest">

<table>
	<tr>
		<td><a href="https://obyte.org"><img src="/android-icon-192x192.png" height="100" width="100"></a></td>
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
<?php
$rate_url="https://api.coinpaprika.com/v1/tickers/gbyte-obyte?quotes=USD,BTC";
$json_array = json_decode(make_443_get ($rate_url), true);
$dollar_value = 0;
$btc_value = 0;
if(!empty($json_array['quotes']['USD']['price'])){
	$dollar_value=round($json_array['quotes']['USD']['price'],2);
}
if(!empty($json_array['quotes']['BTC']['price'])){
	$btc_value=round($json_array['quotes']['BTC']['price'],8);
}

// $rate_url="https://api.coingecko.com/api/v3/simple/price?ids=byteball&vs_currencies=usd,btc";
// $json_array = json_decode(make_443_get ($rate_url), true);
// $dollar_value = 0;
// $btc_value = 0;
// if(!empty($json_array['byteball']['usd'])){
// 	$dollar_value=round($json_array['byteball']['usd'],2);
// }
// if(!empty($json_array['byteball']['btc'])){
// 	$btc_value=round($json_array['byteball']['btc'],8);
// }
?>
<table id="richList" border="0">
	<thead>
		<tr>
			<th width="50">Rank</th>
			<th width="180">Amount (in GBYTE)</th>
			<th width="180">USD (<?php echo $dollar_value; ?>)</th>
			<th width="180">BTC (<?php echo $btc_value; ?>)</th>
			<th width="200"><center>Address</center></th>
		</tr>
	</thead>
	<tbody>
<?php
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
$disclaimers = '';
while( $row = $results->fetchArray(SQLITE3_ASSOC) ){
	$disclaimers .= ($row[ 'address' ] == 'MZ4GUQC7WUKZKKLGAS3H3FSDKLHI7HFO') ? '#'. $i .' <span class="address">'. $row[ 'address' ] .'</span> is address of Obyte distribution fund.<br>' : '';
	$disclaimers .= ($row[ 'address' ] == 'QR542JXX7VJ5UJOZDKHTJCXAYWOATID2') ? '#'. $i .' <span class="address">'. $row[ 'address' ] .'</span> is address of Bittrex exchange.<br>' : '';
	$disclaimers .= ($row[ 'address' ] == 'XCQ3LC6BSRGLPKC6LDQBTHZBKHLGIS5B') ? '#'. $i .' <span class="address">'. $row[ 'address' ] .'</span> is address of Lisk Foundation.<br>' : '';
	$disclaimers .= ($row[ 'address' ] == 'BZUAVP5O4ND6N3PVEUZJOATXFPIKHPDC') ? '#'. $i .' <span class="address">'. $row[ 'address' ] .'</span> is 1% of total supply reserved for the Obyte founder.<br>' : '';
	$disclaimers .= ($row[ 'address' ] == 'TUOMEGAZPYLZQBJKLEM2BGKYR2Q5SEYS') ? '#'. $i .' <span class="address">'. $row[ 'address' ] .'</span> is another address of Obyte distribution fund.<br>' : '';
	$disclaimers .= ($row[ 'address' ] == 'FCXZXQR353XI4FIPQL6U4G2EQJL4CCU2') ? '#'. $i .' <span class="address">'. $row[ 'address' ] .'</span> is address of Obyte Foundation hot-wallet.<br>' : '';
	echo "<tr>";
	echo "<th>#".$i."</th>";
	echo "<td>".number_format ($row[ 'amount' ]/1000000000, 9)."</td>";
	echo "<td>&#x24;".number_format (($row[ 'amount' ]/1000000000)*$dollar_value, 0)."</td>";
	echo "<td>&#x20BF; ".number_format (($row[ 'amount' ]/1000000000)*$btc_value, 8)."</td>";
	echo "<td><a class=\"address\" href=\"https://explorer.obyte.org/#".$row[ 'address' ]."\">".$row[ 'address' ]."</a></td>";
	echo "</tr>";
	$i++;
}

$disclaimers .= 'Rates powered by <a href="https://coinpaprika.com/coin/gbyte-obyte/#!exchanges" target="_blank">CoinPaprika</a>';
//$disclaimers .= 'Rates powered by <a href="https://www.coingecko.com/en/coins/obyte#markets" target="_blank">CoinGecko</a>';
?>
	</tbody>
</table>
<br>

<?php echo $disclaimers; ?>
<br><br>


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
