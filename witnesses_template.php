<!DOCTYPE html>
<html>
<head>
<title>Obyte Order Provider monitoring service</title>
<meta name="Description" CONTENT="Obyte Order Provider monitoring service">
<meta name="keywords" content="obyte, byteball, Order Provider, witness, hub, relay, statistics" />
<link rel="shortcut icon" href="/favicon.ico">
<link rel="icon" type="image/png" sizes="192x192"  href="/android-icon-192x192.png">
<link rel="stylesheet" type="text/css" href="mystyle.css?v3">
</head>
<body class="witnesses">

<table>
	<tr>
		<td><a href="https://obyte.org"><img src="/android-icon-192x192.png" height="100" width="100"></a></td>
		<td style="padding-left: 10px"><center><h1>O<sub>byte</sub> Order Provider monitoring service</h1></center></td>
	</tr>
</table>

<br><br>

<h2>Over the last 12 hours:</h2>
<br>
<table id="witnessList">
	<tr>
		<th></th>
		<th>Rank</th>
		<th><center>Order Provider address</center></th>
		<th><center>Transactions</center></th>
		<th width="130"><center>last seen<br>UTC Timestamp</center></th>
		<th width="80">Income, bytes</th>
		<th width="80">Origin</th>
		<th>Operated by</th>
	
	</tr>
	{{Array}}
	
</table>
<br>
<font size="-1"><i>MC=Main Chain<br>
Updated hourly. Last update: {{update}} UTC<br>
Total active OPs on the network: <b>{{total_active}}</b></i></font>

<br><br><br>


<?php include('footer.php');
