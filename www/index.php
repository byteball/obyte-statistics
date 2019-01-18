<!DOCTYPE html>
<html>
<head>
	<title>Obyte stats</title>
	<link rel="stylesheet" type="text/css" href="mystyle.css">
	<meta name="Description" CONTENT="obyte stats">
	<meta name="keywords" content="obyte, witness, hub, relay, statistics" />
	<meta http-equiv="refresh" content="120" >
	<link rel="icon" href="https://obyte.org/static/android-icon-192x192.png">

	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
</head>

<body>
	<center><h1>O<sub>byte</sub> Stats</h1>

<table>
	<tr>
		<td><img src="https://obyte.org/static/android-icon-192x192.png" height="100" width="100"></td>
		<td width=20></td>
		<td>
			Hub status: <img src="green_button.jpg" height="15" width="15" style="vertical-align: middle"><br>
		</td>
		<td>

		</td>
	</tr>
</table>


<table>
	<tr>
		<td>Connected wallets:</td>
		<td align="center"><b>obyte.org </b><b id="EUConnected"></b></td><td width="10"></td>
	</tr>
</table>



<p><center>
	<table>
		<tr>
			<td><img src="hot-badge-xxl.png" height="30" width="50"></td>
			<td>
				<table>

					<tr>
						<td><font size=-1>
							<a href="/obyteworldmap.php">Click here</a> to see the O<sub>byte</sub> World Map.<br>
							<a href="/Top100Richest.php">Click here</a> to get the O<sub>byte</sub> Top 100 richest list.<br>
							<a href="/heartbeat.php">Click here</a> to see the global O<sub>byte</sub> network stats.<br>
						<a href="/witnesses.php">Click here</a> to get a picture of all Witnesses activity on the network.
						</font></td>
					</tr>
				</table>

			</td>
		</tr>
	</table>
</center></p>
<br><br>Point your wallet to the nearest hub to get efficient messaging communication, faster wallet synch.</p>
	
	<br>New to O<sub>byte</sub>? Check out <a href="https://obyte.org" target="_blank">obyte.org</a>
&nbsp;also on Twitter <a href="https://twitter.com/ByteballOrg" target="_blank" title="Twitter"><i class="fab fa-twitter"></i></a>
&nbsp;Bitcointalk <a href="https://bitcointalk.org/index.php?topic=1608859.0" target="_blank" title="BitcoinTalk thread"><i class="fab fa-bitcoin"></i></a>
&nbsp;Medium <a href="https://medium.com/Byteball" target="_blank" title="Medium"><i class="fab fa-medium"></i></a>
&nbsp;Slack <a href="http://slack.byteball.org" target="_blank" title="Slack"><i class="fab fa-slack"></i></a>
&nbsp;Wiki <a href="https://wiki.byteball.org/" target="_blank" title="Wiki"><i class="fab fa-wikipedia-w"></i></a>
&nbsp;and Discord <a href="https://discord.gg/Qn6JWfT" target="_blank" title="Discord"><i class="fab fa-discord"></i></a>

<p><br></p>

<script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>
<script src="https://code.highcharts.com/stock/highstock.js"></script>
<script src="https://code.highcharts.com/stock/modules/exporting.js"></script>
<table>
	<tr>
		<td><b>Connected wallets history</b></td>
	</tr>
</table>
			
<div id="container" style="height: 200px; min-width: 310px"></div>
<script>
	
	
jQuery.noConflict();
var example = 'basic-line', 
	theme = 'default';
(function($){ // encapsulate jQuery
	$('#EUConnected').html("<img src='./ajax-loader2.gif'/>");
	var processed_json = new Array();   
	$.getJSON('/hub_stats.json', function(data) {
		// Populate series
		for (i = 0; i < data.length; i++){
			processed_json.push([data[i].t, data[i].a]);
		}
		$('#EUConnected').text(processed_json[data.length-1][1]);


		// Create the chart
		Highcharts.stockChart('container', {


			rangeSelector: {
				selected: 1
			},

			credits: {
				enabled: true,
				text: 'Credit: Obyte.org',
				href: "https://obyte.org",
			},

			series: [{
				name: 'Connected Wallets',
				data: processed_json,
				tooltip: {
					valueDecimals: 0
				}
			}]
		});
	})
	.fail( function(d, textStatus, error) {
		alert("getJSON failed, status: " + textStatus + ", error: "+error)
	});

})(jQuery);

</script>

<br><br><br>
<br><br><br>
<br><br><br>


<br><br>
</body>
</html>
