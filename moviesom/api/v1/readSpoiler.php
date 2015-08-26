<?php
  /**
   * Read spoiler.
   * Expects JSON as payload I.e.:
   *  {
   *    "token": "d967a19940bdc4d498d0420a9fb12802ab5857a0a634ab73ae8984c5cf46ab3f9322dd5c1c3f069cc9d226ce47112747976c289cf6ae7b41a8ac72a7dc69c83f",
   *    "recommend_id": 403
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
  } else if (isset($requestJson['recommend_id'])) {
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }
    
      $stmt = $dbh->prepare("SELECT m.title, ms.tmdb_id, rm.spoiler, rm.recommend_by,
                                (SELECT username FROM users WHERE id=rm.recommend_by) AS mail_from, 
                                (SELECT username FROM users WHERE id=rm.recommend_to) AS mail_to 
                              FROM recommend_movies AS rm
                                JOIN movie_sources AS ms ON ms.tmdb_id=rm.tmdb_id
                                JOIN movies AS m ON m.id=ms.movie_id
                              WHERE rm.id=:recommend_id AND rm.recommend_to=:user_id AND rm.is_spoiler_read=0");
      $stmt->bindParam(":recommend_id", $requestJson["recommend_id"]);
      $stmt->bindParam(":user_id", $userId);
      $stmt->execute();
      
      $title = "";
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stmt2 = $dbh->prepare("UPDATE recommend_movies SET is_spoiler_read=1 WHERE id=:recommend_id AND recommend_by=:user_id AND is_spoiler_read=0");
        $stmt2->bindParam(":recommend_id", $requestJson["recommend_id"]);
        $stmt2->bindParam(":user_id", $userId);
        $stmt2->execute();

        // Add points for recommender for spoiling the movie.
        $points = 10;
        $credentials->addPoints($row['recommend_by'], $points);
        // Send spoiler read mail.
        $movieSomMail->mailSpoilerReadPoints($row["mail_from"], $row["mail_to"], $row["tmdb_id"], $row["title"], $row["spoiler"], $points);
        break;
      }
      
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