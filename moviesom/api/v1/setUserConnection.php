<?php
  /**
   * Set user connections.
   * Expects JSON as payload I.e.:
   *  {
   *    "token": "d967a19940bdc4d498d0420a9fb12802ab5857a0a634ab73ae8984c5cf46ab3f9322dd5c1c3f069cc9d226ce47112747976c289cf6ae7b41a8ac72a7dc69c83f",
   *    "email": "john@doe.com"
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
  
  if($loggedIn === false) {
    header('HTTP/1.1 401 Unauthorized');
    $response['message'] = 'Insufficient rights';
    $response['status'] = 401;
  } else if (isset($requestJson['email'])) {
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }
      
      $connection = [];
      
      $stmt = $dbh->prepare("SELECT uc.id, u.id AS uid, u2.id AS uid2, u.username AS user1, u2.username AS user2, uc.consent, uc.consent2 FROM users_connections AS uc
                                JOIN users AS u ON u.id=uc.user_id 
                                JOIN users AS u2 ON u2.id=uc.user_id2 
                              WHERE (user_id=:user_id AND user_id2=(SELECT id FROM users WHERE username=:email)) 
                                OR (user_id2=:user_id AND user_id=(SELECT id FROM users WHERE username=:email))");
      $stmt->bindParam(":email", $requestJson["email"]);
      $stmt->bindParam(":user_id", $userId);
      $stmt->execute();
      $connectionExists = false;
      $consent = 0;
      $consent2 = 0;
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $row["id"];
        if(intval($row["uid"]) == $userId) {
          $consent = 1;
          $row["consent"] = "1";
        }
        if(intval($row["uid2"]) == $userId) {
          $consent2 = 1;
          $row["consent2"] = "1";
        }
        $connection[] = $row;
        $connectionExists = true;
        break;
      }

      if($connectionExists === false) {
        $stmt = $dbh->prepare("INSERT INTO users_connections (user_id, user_id2, consent, consent2) 
                                VALUES (:user_id, (SELECT id FROM users WHERE username=:email), 1, 0)");
        $stmt->bindParam(":email", $requestJson["email"]);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        $connection_id = $dbh->lastInsertId();
        
        // We now select the inserted.
        $stmt = $dbh->prepare("SELECT uc.id, u.id AS uid, u2.id AS uid2, u.username AS user1, u2.username AS user2, uc.consent, uc.consent2 FROM users_connections AS uc
                                  JOIN users AS u ON u.id=uc.user_id 
                                  JOIN users AS u2 ON u2.id=uc.user_id2 
                                WHERE uc.id=:id");
        $stmt->bindParam(":id", $connection_id);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $connection[] = $row;
          break;
        }
      } else {
        $stmt = $dbh->prepare("UPDATE users_connections SET consent=:consent, consent2=:consent2 WHERE id=:id");
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":consent", $consent);
        $stmt->bindParam(":consent2", $consent2);
        $stmt->execute();
      }

      $response['message'] = $connection;
      
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
  
?>