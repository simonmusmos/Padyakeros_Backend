<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// get database connection
include_once '../config/database.php';
 
// instantiate user object
include_once '../class/user.php';
$data = json_decode(file_get_contents("php://input"));
// $data=json_encode($_POST);
// $data=json_decode($data);
$database = new Database();
$db = $database->getConnection();
 
$user = new User($db);
 
// set user property values
if(isset($data->email) && $data->email != ''){
    if(isset($data->password) && $data->password != ''){
        if(isset($data->firstname) && $data->firstname != ''){
            // if(isset($data->middlename) && $data->middlename != ''){
                if(isset($data->lastname) && $data->lastname != ''){
                    if($data->password == $data->c_password){
                        $user->email = $data->email;
                        $user->password = password_hash($data->password, PASSWORD_DEFAULT);
                        $user->firstname = $data->firstname;
                        $user->middlename = "";
                        $user->lastname = $data->lastname;
                        
                        // create the user
                        if($user->createUser()){
                            $user_arr=array(
                                "status" => true,
                                "message" => "User successfully registered"
                            );
                        }
                        else{
                            $user_arr=array(
                                "status" => false,
                                "message" => "Email already Taken!"
                            );
                        }
                    }else{
                        $user_arr=array(
                            "status" => false,
                            "message" => "Passwords does not match".$data->password." ".$data->c_password
                        );
                    }
                }else{
                    $user_arr=array(
                        "status" => false,
                        "message" => "Please insert lastname"
                    );
                }
            // }else{
            //     $user_arr=array(
            //         "status" => false,
            //         "message" => "Please insert middlename"
            //     );
            // }
        }else{
            $user_arr=array(
                "status" => false,
                "message" => "Please insert firstname"
            );
        }
    }else{
        $user_arr=array(
            "status" => false,
            "message" => "Please insert password"
        );
    }
}else{
    $user_arr=array(
        "status" => false,
        "message" => "Please insert email"
    );
}

                    

echo json_encode($user_arr);
?>