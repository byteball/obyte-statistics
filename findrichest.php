<?php 

	include_once '/var/www/where_are_your_mysql_credentials/mysql.php';

	$table_name = "richests_list";
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
  
	$query = "SELECT * FROM $table_name where address='".addslashes($address)."' LIMIT 1"; 


	$q = mysqli_query($mysqli, $query);    
	if ( ! $q ) {
		echo "Problem here...";
		exit;
	}



	if(mysqli_num_rows ( $q )==1){
		while( $row = mysqli_fetch_assoc ( $q ) ){
			echo "Congratulations! You are the <b>#".$row[ 'id' ]."</b> richest with a value of <b>".number_format ( $row[ 'amount' ] , 0 , "." , "," )." </b>bytes.";
		}
	}else{
		echo "Not found.";
	}


?>
