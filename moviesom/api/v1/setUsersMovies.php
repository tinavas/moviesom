<?php
  /**
   * Set users movies settings.
   * Expects JSON as payload I.e.:
   *  {
   *    "token": "d967a19940bdc4d498d0420a9fb12802ab5857a0a634ab73ae8984c5cf46ab3f9322dd5c1c3f069cc9d226ce47112747976c289cf6ae7b41a8ac72a7dc69c83f",
   *    "movie_id": 550,
   *    "tmdb_id": 7468,
   *    "imdb_id": "tt0137523",
   *    "watched": "2",
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
  } else if ((isset($requestJson['movie_id']) || isset($requestJson['tmdb_id']) || isset($requestJson['imdb_id']))) {
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }
      
      $stmt = $dbh->prepare("SELECT m.id, ms.tmdb_id, ms.imdb_id FROM movies AS m JOIN movie_sources AS ms ON ms.movie_id=m.id WHERE m.id=:movie_id OR ms.tmdb_id=:tmdb_id OR ms.imdb_id=:imdb_id GROUP BY m.id");
      $stmt->bindParam(":movie_id", $requestJson["movie_id"]);
      $stmt->bindParam(":tmdb_id", $requestJson["tmdb_id"]);
      $stmt->bindParam(":imdb_id", $requestJson["imdb_id"]);
      $stmt->execute();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $movieExists = true;
        $movie_id = $row["id"];
        $tmdb_id = $row["tmdb_id"];
        $imdb_id = $row["imdb_id"];
        break;
      }

      // Insert the users movies settings
      $stmt = $dbh->prepare(
        "INSERT INTO users_movies (user_id, movie_id, tmdb_id, imdb_id, watched, blu_ray, dvd, digital, other, lend_out)" .
        " VALUES (:user_id, :movie_id, :tmdb_id, :imdb_id, :watched, :blu_ray, :dvd, :digital, :other, :lend_out)" .
        " ON DUPLICATE KEY UPDATE watched=:watched, blu_ray=:blu_ray, dvd=:dvd, digital=:digital, other=:other, lend_out=:lend_out"
      );
      $stmt->bindParam(":user_id", $userId);
      $stmt->bindParam(":movie_id", $movie_id);
      $stmt->bindParam(":tmdb_id", $tmdb_id);
      $stmt->bindParam(":imdb_id", $imdb_id, PDO::PARAM_STR);
      $stmt->bindParam(":watched", $requestJson["watched"], PDO::PARAM_INT);
      $stmt->bindParam(":blu_ray", $requestJson["blu_ray"], PDO::PARAM_INT);
      $stmt->bindParam(":dvd", $requestJson["dvd"], PDO::PARAM_INT);
      $stmt->bindParam(":digital", $requestJson["digital"], PDO::PARAM_INT);
      $stmt->bindParam(":other", $requestJson["other"], PDO::PARAM_INT);
      $stmt->bindParam(":lend_out", $requestJson["lend_out"], PDO::PARAM_STR);
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