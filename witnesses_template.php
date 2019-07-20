<!DOCTYPE html>
<html>
<head>
<title>Obyte Witnesses monitoring service</title>
<meta name="Description" CONTENT="Obyte Witnesses monitoring service">
<meta name="keywords" content="obyte, byteball, witness, hub, relay, statistics" />
<link rel="icon" href="https://obyte.org/static/android-icon-192x192.png">
<link rel="stylesheet" type="text/css" href="mystyle.css">


<table>
	<tr>
		<td><a href="https://obyte.org"><img src="https://obyte.org/static/android-icon-192x192.png" height="100" width="100"></a></td>
		<td style="padding-left: 10px"><center><h1>Obyte Witnesses monitoring service</h1></center></td>
	</tr>
</table>

<br>

<r><br>
<h2>Over the last 12 hours:</h2>
<br>
<table>
	<tr>
		<td></td>
		<td><b>Rank</b></td>
		<td><b><center>Witness Address</center></b></td>
		<td><center><b>Views</b></center></td>
		<td><b><center>in %</center></b></td>
		<td width="100"><center><b>MC unit<br>last seen on</b></center></td>
		<td width="130"><center><b>last seen<br>UTC Timestamp</b></center></td>
		<td width="125"><b>Origin</b></td>
		<td><b>Operated by</b></td>
	
	</tr>
	{{Array}}
	
</table>
<br>
<font size="-1"><i>MC=Main Chain<br>
Updated hourly. Last update: {{update}} UTC<br>
Total active Witnesses on the network: <b>{{total_active}}</b></i></font>

<br><br><br>

<?php include('footer.php');
