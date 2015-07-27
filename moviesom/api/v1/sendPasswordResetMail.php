<?php
  /**
   * Send reset password mail.
   * Expects JSON as payload I.e.:
   *  {
   *    "username": "john@doe.com"
   *  }
   */



  header('HTTP/1.1 500 Internal Server Error');
  header('Content-type: application/json');
  $response = [];
  $response['status'] = 500;

  $requestJson = json_decode(file_get_contents("php://input"), true);
  
  // Make sure username is set and is a valid e-mail address.
  if(isset($requestJson['username']) && filter_var($requestJson['username'], FILTER_VALIDATE_EMAIL)) {
    try {
      $token = hash("sha512", rand() . uniqid("moviesomUID_", true));
      
      $dbh = $db->connect();
      $stmt = $dbh->prepare("SELECT username FROM users WHERE username=:username LIMIT 1");
      $stmt->bindParam(":username", $requestJson['username']);
      $stmt->execute();
      
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $expireDate = date('Y-m-d H:i:s',strtotime("Today +1 day"));
        $stmt2 = $dbh->prepare("INSERT INTO reset_password_tokens (email, token, expire_date) VALUES (:email, :token, :expire_date) ON DUPLICATE KEY UPDATE token=:token, expire_date=:expire_date");
        $stmt2->bindParam(":email", $requestJson['username']);
        $stmt2->bindParam(":token", $token);
        $stmt2->bindParam(":expire_date", $expireDate);
        $stmt2->execute();
        
        $movieSomMail->mailPasswordReset($requestJson['username'], $token);
      }
      
      $response['message'] = 'Reset password request succeeded';
      header('HTTP/1.1 200 OK');
      $response['status'] = 200;
    }
    catch(PDOException $e) {  
      $response['message'] = $e;
    }
  }

    