<?php

		
		include_once '../config/database.php';
		include_once '../class/user.php';
		$header = apache_request_headers();
		// get database connection
		$database = new Database();
		$db = $database->getConnection();
		 
		// prepare user object
		$user = new User($db);
		// set ID property of user to be edited

		$user->product_id = $_POST['product_id'];
		$user->quantity = $_POST['quantity'];
		$user->token=$header['access_token'];
		// read the details of user to be edited
		$resp=$user->createOrder();
		if($resp['result']){
		    // get retrieved row
		    // create array
		    $user_arr=array(
		        "status" => true,
		        "message" => 'You have successfully ordered this product.'
		    );
		}
		else{
		    $user_arr=array(
		        "status" => false,
		        "message" => $resp['message']
		    );
		}
		// make it json format
		// echo $resp;
		print_r(json_encode($user_arr));
?>