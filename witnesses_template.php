<!DOCTYPE html>
<html>
<head>
<title>Obyte Witnesses monitoring service</title>
<meta name="Description" CONTENT="Obyte Witnesses monitoring service">
<meta name="keywords" content="obyte, byteball, witness, hub, relay, statistics" />
<link rel="icon" href="https://obyte.org/static/android-icon-192x192.png">
<link rel="stylesheet" type="text/css" href="mystyle.css?v2">


<table>
	<tr>
		<td><a href="https://obyte.org"><img src="https://obyte.org/static/android-icon-192x192.png" height="100" width="100"></a></td>
		<td style="padding-left: 10px"><center><h1>O<sub>byte</sub> Witnesses monitoring service</h1></center></td>
	</tr>
</table>

<br><br>

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

<div id="witnessTable"></div>

<script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>
<script>
jQuery.noConflict();
(function($){ // encapsulate jQuery
	$('#witnessTable').html("<img src='./ajax-loader2.gif'/>");
	var processed_json = new Array();
	function draw_cell(cell_value) {
		var cell_class = '';
		if (typeof cell_value == 'boolean') {
			cell_class = 'witnessing-own';
			cell_value = 'Yes';
		}
		else if (cell_value) {
			cell_class = 'witnessing-other';
			cell_value = 'Yes';
		}
		else {
			cell_value = 'No';
		}
		return '<td class="'+ cell_class +'">'+ cell_value +'</td>';
	}
	$.getJSON('/obyte_witnesses.json', function(data) {
		var table_rows = '';
		var rows = Object.keys(data.table);
		table_rows = '<thead><tr>';
		table_rows = '<th></th>';
		var columns = Object.keys(data.table[rows[0]]);
		for (i = 0; i < columns.length; i++){
			table_rows += '<th>'+ columns[i] +'</td>';
		}
		table_rows += '</tr></thead>';
		table_rows += '<tbody>';
		for (i = 0; i < rows.length; i++){
			var cells = Object.keys(data.table[rows[i]]);
			table_rows += '<tr>';
			table_rows += '<th>'+ rows[i] +'</td>';
			for (j = 0; j < cells.length; j++){
				table_rows += draw_cell(data.table[rows[i]][cells[j]]);
			}
			table_rows += '</tr>';
		}
		table_rows += '</tbody>';
		$('#witnessTable').html('<h2>WW-WW-TWL*</h2>* Which witnesses (columns) have which witnesses (rows) in their witness list<br><br><table>'+ table_rows +'</table><br>Updated every 10 minutes. Last update: '+ data.last_updated);

	})
	.fail( function(d, textStatus, error) {
		alert("getJSON failed, status: " + textStatus + ", error: "+error)
	});

})(jQuery);

</script>

<?php include('footer.php');
