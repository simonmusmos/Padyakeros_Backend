<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// include database and object files
include_once '../config/database.php';
include_once '../class/user.php';
 
// get database connection
$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));
// $data=json_encode($_POST);
// $data=json_decode($data);
// prepare user object
$user = new User($db);
// set ID property of user to be edited
$user->uid = $data->uid;
$user->firstname = $data->firstname;
$user->lastname = $data->lastname;
$user->middlename = $data->middlename;
$user->address = $data->address;
$user->contact = $data->contact;
// $user->fbid = $data->fbid;
// read the details of user to be edited
$resp=$user->updateProfile();
if($resp['result']){
    // get retrieved row
    // create array
    $user_arr=array(
        "status" => true,
        "message" => 'User Successfully Updated!'
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