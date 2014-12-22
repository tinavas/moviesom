<?php
  /**
   * Login.
   * Expects JSON as payload I.e.:
   *  {
   *    "username": "john@doe.nl",
   *    "password": "thesecret"
   *  }
   */
  header('HTTP/1.1 500 Internal Server Error');
  header('Content-type: application/json');
  $response = [];
  $response['status'] = 500;

  $requestJson = json_decode(file_get_contents("php://input"), true);
  // Credentials should be valid.
  if(isset($requestJson['token']) && strlen($requestJson['token']) > 0) {
    $credentials->checkLoginToken($requestJson['token']);
  } else {
    $credentials->checkCredentials($requestJson['username'], $requestJson['password']);
  }
  
  $loggedIn = $credentials->hasMoviesomAccess();
  
  // Check if sufficient privileges.
  if($loggedIn === false) {
    header('HTTP/1.1 401 Unauthorized');
    $response['message'] = 'Insufficient rights';
    $response['status'] = 401;
  } else {  
      try {
        $dbh = $db->connect();
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if ($dbh->inTransaction() === false) {
          $dbh->beginTransaction();
        }
        $token = $credentials ->generateNewLoginToken();
        $userId = $credentials->getUserId();
        $stmt = $dbh->prepare("INSERT login_tokens (user_id, token, ip) VALUES (:user_id, :token, :ip) ON DUPLICATE KEY UPDATE token=:token");
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":ip", $_SERVER['REMOTE_ADDR']);
        $stmt->execute();
        
        if($dbh->commit()) {
          header('HTTP/1.1 200 OK');
          $response['status'] = 200;
          $response['loginToken'] = $token;
        } else {
          $response['message'] = '';
        }
      }
      catch(PDOException $e) {  
        $response['message'] = $e;
      }
  
    header('HTTP/1.1 200 OK');
    $response['status'] = 200;
  }
  
  $response['execTime'] = $execTime->getTime();
  echo json_encode($response);
  
?>