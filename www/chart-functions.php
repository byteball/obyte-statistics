<?php


function show_chart( $args ){

	?>
	
	<div id="<?php echo $args['container_id']; ?>" style="height: 400px; min-width: 310px; width: 100%; margin-bottom: 40px;"></div>

	<script>
	jQuery.noConflict();

	var example = 'compare', 
		theme = 'default';
	
	(function($){ // encapsulate jQuery
	
		var seriesOptions = [],
		seriesCounter = 0,

		names = [<?php

		foreach ( $args[ 'params' ] as $param ){
			echo "'" . $param[ 'name' ] . "',";
		}

		?>];

		var multi_array=new Array();

		<?php

		foreach ( $args[ 'params' ] as $param ){
			echo "\nvar processed_" . $param[ 'json_id' ] ." = new Array();";
		}

		?>


		$.getJSON('/<?php echo $args['json']; ?>', function(data) {

			 // Populate series
			for (i = 0; i < data.length; i++){

				<?php

				foreach ( $args[ 'params' ] as $param ){
					echo "\nprocessed_" . $param[ 'json_id' ] .".push([data[i].t, data[i]." . $param[ 'json_id' ] ."]);";
				}

				?>

			}

			<?php

			foreach ( $args[ 'params' ] as $param ){
				echo "\nmulti_array.push(processed_" . $param[ 'json_id' ] .");";
			}

			?>


			/**
			 * Create the chart when all data is loaded
			 * @returns {undefined}
			 */
			function createChart() {

				Highcharts.stockChart('<?php echo $args['container_id']; ?>', {

					title: {
						text: '<?php echo $args['title']; ?>'
					},

					subtitle: {
						text: '<?php echo $args['subtitle']; ?>'
					},

					credits: {
						enabled: true,
						text: 'Credit: Obyte.org',
						href: "https://obyte.org",
					},

					rangeSelector: {
						selected: 4
					},

					yAxis: {
						labels: {

						},
						plotLines: [{
							value: 0,
							width: 2,
							color: 'silver'
						}]
					},

					plotOptions: {
						series: {
							compare: '<?php echo $args[ 'plotOptions_compare' ]; ?>',
							showInNavigator: true
						}
					},

					tooltip: {
						pointFormat: '<?php echo $args[ 'tooltip_pointFormat' ]; ?>',
						valueDecimals: <?php echo $args[ 'tooltip_valueDecimals' ]; ?>,
						split: <?php echo $args[ 'tooltip_split' ]; ?>
					},

					series: seriesOptions
				});
			}

			$.each(names, function (i, name) {


				seriesOptions[i] = {
					name: name,
					data: multi_array[i],
				};

				//window.alert(i);
				// As we're loading the data asynchronously, we don't know what order it will arrive. So
				// we keep a counter and create the chart when all the data is loaded.

				seriesCounter += 1;

				if (seriesCounter === names.length) {
					createChart();
				}

			});

		});
	})(jQuery);
	
	</script>

	<?php

}

