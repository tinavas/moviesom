<?php
  /**
   * Get movie ratings.
   * Expects JSON as payload I.e.:
   *  {
   *    "movie_tmdb_ids": [
   *      {id: "550"},
   *      {id: "289732"}
   *    ]
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
    // We need at least one of the following arrays in order to proceed with searching.
  if($loggedIn === false) {
    header('HTTP/1.1 401 Unauthorized');
    $response['message'] = 'Insufficient rights';
    $response['status'] = 401;
  } else if (isset($requestJson['tmdb_id'])) {
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $stmt = $dbh->prepare("SELECT rm.id, u.username, rm.spoiler FROM recommend_movies AS rm 
                                JOIN users AS u ON rm.recommend_by=u.id
                              WHERE rm.tmdb_id=:tmdb_id
                                AND (rm.spoiler IS NOT NULL AND rm.spoiler<>'')
                                AND (rm.recommend_to=:user_id OR rm.recommend_by=:user_id) GROUP BY rm.recommend_by ORDER BY rm.added DESC");
      $stmt->bindValue(":tmdb_id", $requestJson['tmdb_id']);
      $stmt->bindParam(":user_id", $userId);
      $stmt->execute();
      $ratings = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $ratings[] = $row;
      }
      $response["message"] = $ratings;
      
      header('HTTP/1.1 200 OK');
      $response['status'] = 200;
    }
    catch(PDOException $e) {  
      $response['message'] = $e;
    }
  }
  
?>