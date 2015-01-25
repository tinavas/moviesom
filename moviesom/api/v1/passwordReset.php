<?php
  /**
   * Reset password.
   * Expects JSON as payload I.e.:
   *  {
   *    "token": "bladiebla",
   *    "password": "bladiebla",
   *    "password2": "bladiebla"
   *  }
   */

  header('HTTP/1.1 500 Internal Server Error');
  header('Content-type: application/json');
  $response = [];
  $response['status'] = 500;

  $requestJson = json_decode(file_get_contents("php://input"), true);
  
  // All fields should be set and new-password and new-password2 should be the same.
  if (isset($requestJson['token']) &&
      isset($requestJson['password']) && strlen($requestJson['password']) >= 8 && isset($requestJson['password2']) &&
      strcmp($requestJson['password'], $requestJson['password2']) == 0 ) {
    $bCryptPw = password_hash($requestJson['password'], PASSWORD_BCRYPT, array("cost" => 10));
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }

      $stmt = $dbh->prepare("UPDATE users SET password=:password WHERE username=(SELECT email FROM reset_password_tokens WHERE token=:token)");
      $stmt->bindParam(":token", $requestJson["token"]);
      $stmt->bindParam(":password", $bCryptPw);
      $stmt->execute();

      $stmt = $dbh->prepare("DELETE FROM reset_password_tokens WHERE token=:token");
      $stmt->bindParam(":token", $requestJson["token"]);
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
