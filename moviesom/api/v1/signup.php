<?php
  /**
   * Create an account.
   * Expects JSON as payload I.e.:
   *  {
   *    "username": "john@doe.nl",
   *    "password": "thesecret",
   *    "password2": "thesecret"
   *  }
   */



  header('HTTP/1.1 500 Internal Server Error');
  header('Content-type: application/json');
  $response = [];
  $response['status'] = 500;
  
  $requestJson = json_decode(file_get_contents("php://input"), true);
  
  /**
   * Check if all required values exist and password and password2 are the same.
   */
  if (isset($requestJson['username']) && isset($requestJson['password'])
      && strlen($requestJson['password']) >= 8 && isset($requestJson['password2'])
      && strcmp($requestJson['password'], $requestJson['password2']) == 0) {
    $bCryptPw = password_hash($requestJson['password'], PASSWORD_BCRYPT, array("cost" => 10));
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }

      $stmt = $dbh->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
      $stmt->bindParam(":username", $requestJson["username"]);
      $stmt->bindParam(":password", $bCryptPw);
      $stmt->execute();
      
      if($dbh->commit()) {
        header('HTTP/1.1 200 OK');
        $response['status'] = 200;
      } else {
        $response['message'] = '';
      }
    }
    catch(PDOException $e) {  
      $response['message'] = $e;
    }
  }
  
  $response['execTime'] = $execTime->getTime();
  echo json_encode($response);
  
?>