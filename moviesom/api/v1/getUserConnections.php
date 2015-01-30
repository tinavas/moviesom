<?php
  /**
   * Get user connections.
   * Expects JSON as payload I.e.:
   *  {
   *    "token": "d967a19940bdc4d498d0420a9fb12802ab5857a0a634ab73ae8984c5cf46ab3f9322dd5c1c3f069cc9d226ce47112747976c289cf6ae7b41a8ac72a7dc69c83f"
   *  }
   */

  header('HTTP/1.1 500 Internal Server Error');
  header('Content-type: application/json');
  $response = [];
  $response['status'] = 500;
  
  $requestJson = json_decode(file_get_contents("php://input"), true);

  // Login token should be valid.
  if(isset($requestJson['token']) && strlen($requestJson['token']) > 0) {
    $credentials->checkLoginToken($requestJson['token']);
  }
  
  $loggedIn = $credentials->hasMoviesomAccess();
  $userId = $credentials->getUserId();

  // We need at least one of the following arrays in order to proceed with searching.
  if($loggedIn === false) {
    header('HTTP/1.1 401 Unauthorized');
    $response['message'] = 'Insufficient rights';
    $response['status'] = 401;
  } else {
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $stmt = $dbh->prepare("SELECT * FROM users_connections WHERE (user_id=:user_id OR user_id2=:user_id) AND consent=1 AND consent2=1");
      $stmt->bindParam(":user_id", $userId);
      $stmt->execute();
      $userStats = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $userStats["connections"] = $row;
      }
      
      $stmt = $dbh->prepare("SELECT * FROM users_connections WHERE user_id2=:user_id AND (consent2=0)");
      $stmt->bindParam(":user_id", $userId);
      $stmt->execute();
      $userStats = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $userStats["requests"] = $row;
      }
      
      $response["message"] = $userStats;
      header('HTTP/1.1 200 OK');
      $response['status'] = 200;
    }
    catch(PDOException $e) {  
      $response['message'] = $e;
    }
  }
  
?>