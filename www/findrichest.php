<?php 

//	$home_dir = $_SERVER['HOME'];
//	if (!$home_dir)
//		$home_dir = $_SERVER['DOCUMENT_ROOT'].'/../..';
	$stats_db = new SQLite3('../stats.sqlite', SQLITE3_OPEN_READONLY);

	$address=trim($_POST['address']);
  
	if( strlen( $address ) > 0 ){

		if( ! preg_match( "@^[A-Z2-7]{32}$@", $address ) ){

			echo "Incorrect Byteball address.";
			exit;
		}


	} else {
		echo "Empty value.";
		exit;
	}
  
	$query = "SELECT * FROM richlist where address='".addslashes($address)."' LIMIT 1"; 


	$results = $stats_db->query($query);    
	if ( ! $results ) {
		echo "Problem here...";
		exit;
	}



	if( $row = $results->fetchArray(SQLITE3_ASSOC) ){
		echo "Congratulations! You are the <b>#".$row[ 'id' ]."</b> richest with a value of <b>".number_format ( $row[ 'amount' ] , 0 , "." , "," )." </b>bytes.";
	}else{
		echo "Not found.";
	}


?>
