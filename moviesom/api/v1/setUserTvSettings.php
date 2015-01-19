<?php
  /**
   * Set user movie settings.
   * Expects JSON as payload I.e.:
   *  {
   *    "token": "d967a19940bdc4d498d0420a9fb12802ab5857a0a634ab73ae8984c5cf46ab3f9322dd5c1c3f069cc9d226ce47112747976c289cf6ae7b41a8ac72a7dc69c83f",
   *    "tv_id": 550,
   *    "tmdb_id": 7468,
   *    "imdb_id": "tt0137523",
   *    "watched": "2",
   *    "want_to_watch": 1,
   *    "blu_ray": 0,
   *    "dvd": 1,
   *    "digital": 0,
   *    "other": 0,
   *    "lend_out": "John Doe"
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
  } else if ((isset($requestJson['tv_id']) || isset($requestJson['tmdb_id']) || isset($requestJson['imdb_id']))) {
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }
      
      $stmt = $dbh->prepare("SELECT tv.id, ts.tmdb_id, ts.imdb_id FROM tv JOIN tv_sources AS ts ON ts.tv_id=tv.id WHERE tv.id=:tv_id OR ts.tmdb_id=:tmdb_id OR ts.imdb_id=:imdb_id GROUP BY tv.id");
      $stmt->bindParam(":tv_id", $requestJson["tv_id"]);
      $stmt->bindParam(":tmdb_id", $requestJson["tmdb_id"]);
      $stmt->bindParam(":imdb_id", $requestJson["imdb_id"]);
      $stmt->execute();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $movieExists = true;
        $tv_id = $row["id"];
        $tmdb_id = $row["tmdb_id"];
        $imdb_id = $row["imdb_id"];
        break;
      }

      // Insert the users tv settings
      $stmt = $dbh->prepare(
        "INSERT INTO users_tv (user_id, tv_id, tmdb_id, imdb_id, watched, want_to_watch, blu_ray, dvd, digital, other, lend_out)" .
        " VALUES (:user_id, :tv_id, :tmdb_id, :imdb_id, :watched, :want_to_watch, :blu_ray, :dvd, :digital, :other, :lend_out)" .
        " ON DUPLICATE KEY UPDATE watched=:watched, want_to_watch=:want_to_watch, blu_ray=:blu_ray, dvd=:dvd, digital=:digital, other=:other, lend_out=:lend_out"
      );
      $stmt->bindParam(":user_id", $userId);
      $stmt->bindParam(":tv_id", $tv_id);
      $stmt->bindParam(":tmdb_id", $tmdb_id);
      $stmt->bindParam(":imdb_id", $imdb_id, PDO::PARAM_STR);
      $zero = 0;
      $emptyString = "";
      $stmt->bindParam(":watched", $zero, PDO::PARAM_INT);
      $stmt->bindParam(":want_to_watch", $requestJson["want_to_watch"], PDO::PARAM_INT);
      $stmt->bindParam(":blu_ray", $zero, PDO::PARAM_INT);
      $stmt->bindParam(":dvd", $zero, PDO::PARAM_INT);
      $stmt->bindParam(":digital", $zero, PDO::PARAM_INT);
      $stmt->bindParam(":other", $zero, PDO::PARAM_INT);
      $stmt->bindParam(":lend_out", $emptyString, PDO::PARAM_STR);
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
  
?>