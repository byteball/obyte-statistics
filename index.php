<!DOCTYPE html>
<html>
<head>
	<title>Byteball official alternative hubs and redistributive witness service</title>
	<link rel="stylesheet" type="text/css" href="mystyle.css">
	<meta name="Description" CONTENT="byteball hub and redistributive witness service">
	<meta name="keywords" content="byteball, witness, hub, relay, statistics" />
	<meta http-equiv="refresh" content="120" >
	<link rel="icon" href="./icon_16x16@2x.png">

	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">


</head>

<body>
<center><h1>Your gate to the Byteball network!</h1>
		<font size="+2">Europe - France<br></font>

<table>
	<tr>
		<td><img src="icon-white-outline-1024.png" height="100" width="100"></td>
		<td width=20></td>
		<td>

		Hubs status: <img src="green_button.jpg" height="15" width="15"><br>



		Paired with master: byteball.org/bb<br>
		Min client: 2.3.0<font size=-1></font>
		</td>
		<td>

		</td>
	</tr>
</table>


<table>
	<tr>
		<td>Connected wallets:</td>
		<td><img src="./europeenne-drapeau-union-icone-5177-48.png" height="20" width="20" alt="byteball.fr"></td><td align="center"><b>byteball.fr </b><b id="EUConnected"></b></td><td width="10"></td>

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
						<a href="./byteballworldmap.php">Click here</a> to see the Byteball World Map.<br>
						<a href="https://byteball.fr/Top100ByteballRichest.html">Click here</a> to get the Byteball Top 100 richest list.<br>
						<a href="https://byteball.fr/heartbeat.php">Click here</a> to see the global network stats.<br>
						<a href="https://byteball.fr/stats.php">Click here</a> to get a picture of all Witnesses activity on the network.
						</font></td>
					</tr>
				</table>

			</td>
		</tr>
	</table>
</center></p>
<p><br>We provide a worldwide gate to the Byteball network with our hub in France paired with master byteball.org/bb, together with usefull statistics. 
<br><br>Point your wallet to the nearest hub to get efficient messaging communication, faster wallet synch.</p>
<p><center>
	<table>
		<tr><td><b>In your wallet settings &rarr; hub, set </b></td><td></td><td><b><em>byteball.fr/bb</em></b> (EUROPE, France)</td></tr>

	</table>
	
	
	
</center></p>
	
	<br>New to Byteball ? Check <a href="http://www.byteball.org" target="_blank">www.byteball.org</a>
&nbsp;also on Twitter <a href="https://twitter.com/ByteballOrg" target="_blank" title="Twitter"><i class="fa fa-twitter"></i></a>
&nbsp;Bitcointalk <a href="https://bitcointalk.org/index.php?topic=1608859.0" target="_blank" title="BitcoinTalk thread"><i class="fa fa-bitcoin"></i></a>
&nbsp;Medium <a href="https://medium.com/@Byteball" target="_blank" title="Medium"><i class="fa fa-medium"></i></a>
&nbsp;Slack <a href="http://slack.byteball.org" target="_blank" title="Slack"><i class="fa fa-slack"></i></a>
&nbsp;and Wiki <a href="https://wiki.byteball.org/" target="_blank" title="Wiki"><i class="fa fa-wikipedia-w"></i></a>
		  
<p><br>

<script src="https://byteball.fr/graph_framework/jquery-3.2.1.min.js"></script>
<script src="https://byteball.fr/graph_framework/code/highstock.js"></script>
<script src="https://byteball.fr/graph_framework/code/modules/exporting.js"></script>
<table>
	<tr>
		<td><img src="./europeenne-drapeau-union-icone-5177-48.png" height="42" width="42"></td><td width="10"></td><td><b>EUROPE France - byteball.fr/bb - Connected wallets history</b></td>
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
	$.getJSON('https://byteball.fr/hub_stats.json', function(data) {
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
				text: 'Credit: Byteball.fr',
				href: "https://byteball.fr",
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
<br>Thank you.<br><font size=-1><em>contact@byteball.fr</em></font></p>


<br><br>
</body>
</html>
