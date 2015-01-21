<?php
  /**
   * Get tv progress.
   * Expects JSON as payload I.e.:
   *  {
   *    "token": "d967a19940bdc4d498d0420a9fb12802ab5857a0a634ab73ae8984c5cf46ab3f9322dd5c1c3f069cc9d226ce47112747976c289cf6ae7b41a8ac72a7dc69c83f",
   *    "tv_tmdb_ids": [
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

  if($loggedIn === false) {
    header('HTTP/1.1 401 Unauthorized');
    $response['message'] = 'Insufficient rights';
    $response['status'] = 401;
  }
  else if (isset($requestJson['tv_tmdb_ids'])) {
    // If any of the request variables aren't defined then we create an empty one.
    if(isset($requestJson['tv_tmdb_ids']) == false) $requestJson['tv_tmdb_ids'] = [];
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }
      $tmdbWhereIn = (count($requestJson['tv_tmdb_ids'])) ? implode(',', array_fill(0, count($requestJson['tv_tmdb_ids']), '?')): "";
      if(strlen($tmdbWhereIn) == 0) $tmdbWhereIn = "NULL";
      $stmt = $dbh->prepare("SELECT te.tmdb_tv_id AS tmdb_id, tv.number_of_episodes, COUNT(*) AS watched FROM tv_episodes AS te 
                                JOIN users_tv_episodes AS ute ON ute.tv_episode_id=te.id
                                JOIN tv_sources AS ts ON ts.tmdb_id=te.tmdb_tv_id
                                JOIN tv ON tv.id=ts.tv_id
                              WHERE te.tmdb_tv_id IN ({$tmdbWhereIn})
                                AND ute.user_id=?
                                AND te.season_number>0
                              GROUP BY te.tmdb_tv_id");
      $pos = 0;
      foreach ($requestJson['tv_tmdb_ids'] as $k => $id) {
        $pos++;
        $stmt->bindValue($pos, $id["id"]);
      }
      $pos++;
      $stmt->bindValue($pos, $userId);

      $stmt->execute();
      $ratings = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $ratings[] = $row;
      }
      $response["message"] = $ratings;
      
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
