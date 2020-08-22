<?php

		
		include_once '../config/database.php';
		include_once '../class/user.php';
		// get database connection
		$database = new Database();
		$db = $database->getConnection();
		 
		// prepare user object
		$user = new User($db);
		// set ID property of user to be edited

		$user->product_id = $_POST['product_id'];
		// $user->name = $_POST['name'];
		$user->quantity = $_POST['quantity'];
		// read the details of user to be edited
		$resp=$user->addQuantity();
		if($resp['result']){
		    // get retrieved row
		    // create array
		    $user_arr=array(
		        "status" => true,
		        "access_token" => 'Quantity has been added'
		    );
		}
		else{
		    $user_arr=array(
		        "status" => false,
		        "message" => $resp['message']
		    );
		}
		// make it json format
		// echo $user->login();
		print_r(json_encode($user_arr));
?>