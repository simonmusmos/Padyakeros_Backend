<?php
class User{
 
    // database connection and table name
    private $conn;
    private $table_name = "users";
 
    // object properties
    public $quantity;
    public $name;
    public $product_id;
    public $id;
    public $email;
    public $date;
    public $result;
    public $password;
    public $created;
    public $attempts;
    public $datefail;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }
    // signup user
    function createUser(){
    
        if($this->isAlreadyExist()){
            return false;
        }
        // query to insert record
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    email=:email, password=:password, date_created=:date_created ";
    
        // prepare query
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        // $this->email=htmlspecialchars(strip_tags($this->email));
        // $this->password=htmlspecialchars(strip_tags($this->password));
        // bind values
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":date_created", date("Y-m-d H:i:s"));
        // execute query
        if($stmt->execute()){
            $this->id = $this->conn->lastInsertId();
            $query = "INSERT INTO
                    `user_profile`
                SET
                    id=:id, firstname=:firstname, lastname=:lastname, middlename=:middlename ";
    
            // prepare query
            $stmt = $this->conn->prepare($query);
            
            // sanitize
            // $this->email=htmlspecialchars(strip_tags($this->email));
            // $this->password=htmlspecialchars(strip_tags($this->password));
            // bind values
            $stmt->bindParam(":id", $this->id);
            $stmt->bindParam(":firstname", $this->firstname);
            $stmt->bindParam(":middlename", $this->middlename);
            $stmt->bindParam(":lastname", $this->lastname);
            if($stmt->execute()){
                return true;
            }
            
        }
    
        return false;
        
    }
    // login user
    function login(){
        $response['result']=true;
        // select all query
        $query = "SELECT
                    `id`, `email`, `password`, `date_created`
                FROM
                    " . $this->table_name . " 
                WHERE
                    email='".$this->email."'";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // execute query
        $stmt->execute();
        if($stmt->rowCount()>0){
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($this->password, $row['password'])){
                // return true;
                $this->id=$row['id'];
                // $this->datefail=$row['date_fail'];
                $this->attempts=$row['attempts'];

                $response['token']=$this->createToken();
                $response['result']=true;
                return $response;
            }else{
                $this->id=$row['id'];
                // $this->datefail=$row['date_fail'];
                $this->attempts=$row['attempts'];
                $this->addAttempts();
                 $response['result']=false;
                // return false;
            }
        }else{
             $response['result']=false;
            // return false;
        }
        return $response;
    }
    function tokenChecker(){
        $response['result']=true;
        // select all query
        $query = "SELECT
                    *
                FROM
                    `user_sessions`
                WHERE
                    `user_sessions`.`value`='".$this->token."' AND `user_sessions`.`date` BETWEEN '".date("Y-m-d H:i:s", strtotime("-181 minutes"))."' AND '".date("Y-m-d H:i:s")."'";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // execute query
        $stmt->execute();
        if($stmt->rowCount()>0){
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $response['id']=$row['user_id'];
        }else{
             $response['result']=false;
             $response['message']=date("Y-m-d H:i:s", strtotime("-181 minute"));
            // return false;
        }
        return $response;
    }
    function getProfile(){
        $response['result']=true;
        // select all query
        $query = "SELECT
                    user_profile.firstname, user_profile.middlename, user_profile.lastname, users.email, user_profile.status, user_profile.bikeID, user_profile.address, user_profile.contact
                FROM
                    user_profile
                LEFT JOIN users
                    ON users.id = user_profile.id
                WHERE
                    user_profile.id='".$this->uid."'";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // execute query
        $stmt->execute();
        if($stmt->rowCount()>0){
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $response['profile'] = $row;
        }else{
             $response['result']=false;
            // return false;
        }
        return $response;
    }
    function updateData($tbl, $column, $condition){
        $query = "UPDATE
                        ".$tbl."
                    SET
                        ".$column."
                        ".$condition;
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()){
            return true;
        }else{
            return false;
        }
    }
    function updateProfile(){
        $userData=$this->getData("user_profile", " WHERE id='".$this->uid."'", "*");
        if($userData['result']){
            if($this->firstname==""){
                $this->firstname=$userData['firstname'];
            }

            if($this->lastname==""){
                $this->lastname=$userData['lastname'];
            }

            if($this->address==""){
                $this->address=$userData['address'];
            }

            if($this->contact==""){
                $this->contact=$userData['contact'];
            }
            $response['result']=$this->updateData("user_profile", "firstname='".$this->firstname."', middlename='".$this->middlename."', lastname='".$this->lastname."', contact='".$this->contact."', address='".$this->address."'", " WHERE id='".$this->uid."'");
        }else{
            $response['result']=false;
            $response['message']="Cannot fetch user info";
        }
        
        return $response;
    }
    function updateProfileImage(){
        $userData=$this->getData("user_profile", " WHERE id='".$this->uid."'", "*");
        if($userData['result']){
            if($this->image==""){
                $this->firstname=$userData['profileImg'];
            }
            $response['result']=$this->updateData("user_profile", "profileImg='".$this->image."'", " WHERE id='".$this->uid."'");
        }else{
            $response['result']=false;
            $response['message']="Cannot fetch user info";
        }
        
        return $response;
    }
    function changePassword(){
        $userData=$this->getData("users", " WHERE id='".$this->uid."'", "*");
        if(password_verify($this->oldPassword, $userData['password'])){
            if($this->password == $this->rePassword){
                $response['result']=$this->updateData("users", "password='".password_hash($this->password, PASSWORD_DEFAULT)."'", " WHERE id='".$this->uid."'");
            }else{
                $response['result']=false;
                $response['message']="New passwords does not match!";
            }
            
        }else{
            $response['result']=false;
            $response['message']="Old password does not match!";
        }
        
        return $response;
    }
    function getData($tbl, $cond, $key="*"){
        $query = "SELECT ".$key."
            FROM
                ".$tbl."
                ".$cond;
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // execute query
        $stmt->execute();
        $row=array();
        if($stmt->rowCount() > 0){
            $row1 = $stmt->fetch(PDO::FETCH_ASSOC);
            $row=$row1;
            $row['result']=true;
        }
        else{
            $row['result']=false;
            $row['message']='Invalid Product.';
        }
        return $row;
    }
    function getHistory(){
        $query = "SELECT terminals.name, history.date, history.type, history.id, history.bikeID
            FROM
                history
            LEFT JOIN terminals
            ON terminals.id = history.terminalID
            WHERE history.user='".$this->uid."' AND history.type='rent'";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // execute query
        $stmt->execute();
        $row=array();
        $ctr=1;
        if($stmt->rowCount() > 0){
            while($row1 = $stmt->fetch(PDO::FETCH_ASSOC)){
                $row_s['start']=$row1;
                // $row['data'][$ctr]['start']=$row1;
                $query1 = "SELECT terminals.name, history.date, history.type, history.bikeID
                    FROM
                        history
                    LEFT JOIN terminals
                    ON terminals.id = history.terminalID
                    WHERE history.user='".$this->uid."' AND history.type='return' AND history.id > '".$row1['id']."' ORDER BY history.id ASC LIMIT 1";
                // prepare query statement
                $stmt1 = $this->conn->prepare($query1);
                // execute query
                $stmt1->execute();
                if($stmt1->rowCount() > 0){
                    $row2 = $stmt1->fetch(PDO::FETCH_ASSOC);
                    $row_s['end']=$row2;
                    // $row['data'][$ctr]['end']=$row2;
                }else{
                    $row_s['end']['message']="Currently in Ride.";
                    // $row['data']['end']['message']='currently in ride';
                }
                $row['data'][]=$row_s;
                $ctr+=1;
            }
            
            $row['result']=true;
        }
        else{
            $row['result']=false;
            $row['message']='No History Available.';
        }
        return $row;
    }
    function getTerminals(){
        $query = "SELECT terminals.*, COUNT(bikes.id) as number_of_bikes
            FROM
                terminals
            LEFT JOIN bikes
                ON bikes.locationID = terminals.id AND bikes.status = 'available'
            GROUP BY bikes.locationID";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // execute query
        $stmt->execute();
        $row=array();
        if($stmt->rowCount() > 0){
            while($row1 = $stmt->fetch(PDO::FETCH_ASSOC)){
                $row['data'][]=$row1;
            }
            
            $row['result']=true;
        }
        else{
            $row['result']=false;
            $row['message']='No Terminal Available.';
        }
        return $row;
    }
    function getBikes(){
        if($this->locationID == '0' || $this->locationID == ''){
            $addq="";
        }else{
            $addq=" WHERE bikes.locationID='".$this->locationID."'";
        }

        if($this->status == 'all' || $this->status == ''){
            $addq.="";
        }else{
            if($addq==""){
                $addq=" WHERE bikes.status='".$this->status."'";
            }else{
                $addq=" AND bikes.status='".$this->status."'";
            }
        }
        $query = "SELECT bikes.id, bikes.status, terminals.name
            FROM
                bikes
            LEFT JOIN terminals
                ON bikes.locationID = terminals.id".$addq;
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // execute query
        $stmt->execute();
        $row=array();
        if($stmt->rowCount() > 0){
            while($row1 = $stmt->fetch(PDO::FETCH_ASSOC)){
                $row['data'][]=$row1;
            }
            
            $row['result']=true;
        }
        else{
            $row['result']=false;
            $row['message']='No Bikes Available.';
        }
        return $row;
    }
    function rentBike(){
        $profile=$this->getProfile();
        if($profile['profile']['status'] == "no-bike"){
            $query = "SELECT *
                FROM
                    bikes
                WHERE id='".$this->bikeID."' AND bikes.status='available'";
            // prepare query statement
            $stmt = $this->conn->prepare($query);
            // execute query
            $stmt->execute();
            if($stmt->rowCount() > 0){
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->updateData("user_profile", "status='bike-mode', bikeID='".$this->bikeID."'", " WHERE id='".$this->uid."'");
                $this->updateData("bikes", "status='used', locationID='0'", " WHERE id='".$this->bikeID."'");
                $this->locationID=$row['locationID'];
                $this->type="rent";
                $row['result']=$this->insertHistory();
                // true;
                $row['date']=date("Y-m-d H:i:s");
            }
            else{
                $row['result']=false;
                $row['message']='Bike is Not Available.';
            }
        }else{
            $row['result']=false;
            $row['message']='User is currently in Bike Mode.';
        }
        
        return $row;
    }
    function returnBike(){
        $profile=$this->getProfile();
        
        if($profile['profile']['status'] == "bike-mode"){
            $query1 = "SELECT *
                FROM
                    terminals
                WHERE id='".$this->locationID."'";
            // prepare query statement
            $stmt1 = $this->conn->prepare($query1);
            // execute query
            $stmt1->execute();
            if($stmt1->rowCount() > 0){
                $this->updateData("user_profile", "status='no-bike', bikeID='0'", " WHERE id='".$this->uid."'");
                $this->updateData("bikes", "status='available', locationID='".$this->locationID."'", " WHERE id='".$profile['profile']['bikeID']."'");
                $this->type="return";
                $this->bikeID=$profile['profile']['bikeID'];
                $row['result']=$this->insertHistory();
                // true;
            }else{
                $row['result']=false;
                $row['message']='Location Unavailable.';
            }
            
        }
        else{
            $row['result']=false;
            $row['message']='User is not in Bike Mode.';
        }
        return $row;
    }
    function addAttempts(){
        if($this->attempts<5){
            $query = "UPDATE
                        users
                    SET
                        attempts = attempts + ".$this->attempts.",
                        date_fail = ".date('Y-m-d H:i:s')."
                    WHERE
                        id='".$this->id."'";
            $stmt = $this->conn->prepare($query);
            if($stmt->execute()){
                $response['result']=true;
            }
        }
        
    }
    function checkFail(){
        if($this->attempts>=5){
            $diff_time=(strtotime(date("Y/m/d H:i:s"))-strtotime($this->datefail))/60;
            if((int)$diff_time<=5){
                return false;
            }else{
                return true;
            }
        }else{
            return true;
        }
    }
    function insertHistory(){
        $query = "INSERT INTO
                    history
                SET
                history.id='', history.user=:user, history.terminalID=:terminal, history.type=:typevar, history.bikeID=:bike, history.date=:dateval";
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        // $this->email=htmlspecialchars(strip_tags($this->email));
        // $this->password=htmlspecialchars(strip_tags($this->password));
        // bind values
        $stmt->bindParam(":user", $this->uid);
        $stmt->bindParam(":terminal", $this->locationID);
        $stmt->bindParam(":bike", $this->bikeID);
        $stmt->bindParam(":typevar", $this->type);
        $stmt->bindParam(":dateval", date('Y-m-d H:i:s'));
        // execute query
        if($stmt->execute()){
            // $this->id = $this->conn->lastInsertId();
            
            return true;
        }else{
            return false;
        }
    }
    function createToken(){
        $token=$this->generateJWT();
        $query = "INSERT INTO
                    user_sessions
                SET
                    user_id=:id, value=:value, `date`=:dateval";
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        // $this->email=htmlspecialchars(strip_tags($this->email));
        // $this->password=htmlspecialchars(strip_tags($this->password));
        // bind values
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":value", $token);
        $stmt->bindParam(":dateval", date('Y-m-d H:i:s'));
        // execute query
        if($stmt->execute()){
            // $this->id = $this->conn->lastInsertId();
            $this->result=true;
            return $token;
        }
    }
    function addProduct(){
        $query = "INSERT INTO
                    products
                SET
                    name=:name, quantity=:quantity";
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        // $this->email=htmlspecialchars(strip_tags($this->email));
        // $this->password=htmlspecialchars(strip_tags($this->password));
        // bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":quantity", $this->quantity);
    
        // execute query
        if($stmt->execute()){
            return true;
        }
    
        return false;
    }
    function addQuantity(){
        $productCheck=$this->getProduct();
        if($productCheck['result']){
            $query = "UPDATE
                        products
                    SET
                        quantity = quantity + ".$this->quantity."
                    WHERE
                        id='".$this->product_id."'";
            $stmt = $this->conn->prepare($query);
            if($stmt->execute()){
                $response['result']=true;
            }
        }else{
            $response['result']=false;
            $response['message']=$productCheck['message'];
        }
        return $response;
    }
    function createOrder(){
        $response['result']=false;
        $tokenChecker=$this->checkToken();
        if($tokenChecker['result']){
            $productCheck=$this->checkQuantity();
            // return $this->product_id;
            if($productCheck['result']){
                $newquantity=$productCheck['quantity']-$this->quantity;
                $query = "UPDATE
                            products
                        SET
                            quantity ='".$newquantity."'
                        WHERE
                            id='".$this->product_id."'";
                $stmt = $this->conn->prepare($query);
                if($stmt->execute()){
                    $response['result']=true;
                }
            }else{
                $response['message']=$productCheck['message'];
            }
        }else{
            $response['message']="Invalid Access Token.";
        }
        return $response;
    }
    function generateJWT(){
        // Create token header as a JSON string
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        // Create token payload as a JSON string
        $payload = json_encode(['user_email' => $this->id]);
        
        // Encode Header to Base64Url String
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        
        // Encode Payload to Base64Url String
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        // Create Signature Hash
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'abC123!', true);
        
        // Encode Signature to Base64Url String
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        // Create JWT
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
        
        return $jwt;
    }
    function checkToken(){
        $query = "SELECT *
            FROM
                user_sessions 
            WHERE
                value='".$this->token."' AND user_sessions.date BETWEEN '".date("Y-m-d H:i:s", strtotime("-16 minutes"))."' AND '".date("Y-m-d H:i:s")."'";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // execute query
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($stmt->rowCount() > 0){
            $row['result']=true;
        }
        else{
            $row['result']=false;
        }
        return $row;
    }
    function getProduct(){
        $query = "SELECT *
            FROM
                products 
            WHERE
                id='".$this->product_id."'";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // execute query
        $stmt->execute();
        $row=array();
        if($stmt->rowCount() > 0){
            $row1 = $stmt->fetch(PDO::FETCH_ASSOC);
            $row=$row1;
            $row['result']=true;
        }
        else{
            $row['result']=false;
            $row['message']='Invalid Product.';
        }
        return $row;
    }
    function checkQuantity(){
        $query = "SELECT *
            FROM
                products 
            WHERE
                id='".$this->product_id."'";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // execute query
        $stmt->execute();
        $row=array();
        if($stmt->rowCount() > 0){
            $row1 = $stmt->fetch(PDO::FETCH_ASSOC);
            $row=$row1;
            if($row['quantity']>=$this->quantity){
                $row['result']=true;
            }else{
                $row['result']=false;
                $row['message']="Failed to order this product due to unavailability of the stock";
            }
        }
        else{
            $row['result']=false;
            $row['message']='Invalid Product.';
        }
        return $row;
    }
    function isAlreadyExist(){
        $query = "SELECT *
            FROM
                " . $this->table_name . " 
            WHERE
                email='".$this->email."'";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // execute query
        $stmt->execute();
        if($stmt->rowCount() > 0){
            return true;
        }
        else{
            return false;
        }
    }
}