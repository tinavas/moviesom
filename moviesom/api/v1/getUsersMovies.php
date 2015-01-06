<?php
  /**
   * Get users movies settings.
   * Expects JSON as payload I.e.:
   *  {
   *    "token": "d967a19940bdc4d498d0420a9fb12802ab5857a0a634ab73ae8984c5cf46ab3f9322dd5c1c3f069cc9d226ce47112747976c289cf6ae7b41a8ac72a7dc69c83f",
   *    "movie_ids": [
   *      {id: "1"},
   *      {id: "2"}
   *    ],
   *    "tmdb_ids": [
   *      {id: "550"},
   *      {id: "289732"}
   *    ],
   *    "imdb_ids": [
   *      {id: "tt0137523"},
   *      {id: "tt3560742"}
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
  if($loggedIn === false) {
    header('HTTP/1.1 401 Unauthorized');
    $response['message'] = 'Insufficient rights';
    $response['status'] = 401;
  } else if (isset($requestJson['movie_ids']) || isset($requestJson['tmdb_ids']) || isset($requestJson['imdb_ids'])) {
    // If any of the request variables aren't defined then we create an empty one.
    if(isset($requestJson['movie_ids']) == false) $requestJson['movie_ids'] = [];
    if(isset($requestJson['tmdb_ids']) == false) $requestJson['tmdb_ids'] = [];
    if(isset($requestJson['imdb_ids']) == false) $requestJson['imdb_ids'] = [];
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }
      $movieWhereIn = (count($requestJson['movie_ids'])) ? implode(',', array_fill(0, count($requestJson['movie_ids']), '?')) : "";
      if(strlen($movieWhereIn) == 0) $movieWhereIn = "NULL";
      $tmdbWhereIn = (count($requestJson['tmdb_ids'])) ? implode(',', array_fill(0, count($requestJson['tmdb_ids']), '?')): "";
      if(strlen($tmdbWhereIn) == 0) $tmdbWhereIn = "NULL";
      $imdbWhereIn = (count($requestJson['imdb_ids'])) ? implode(',', array_fill(0, count($requestJson['imdb_ids']), '?')) : "";
      if(strlen($imdbWhereIn) == 0) $imdbWhereIn = "NULL";
      $stmt = $dbh->prepare("SELECT * FROM users_movies WHERE user_id=? AND (movie_id IN({$movieWhereIn}) OR tmdb_id IN({$tmdbWhereIn}) OR imdb_id IN({$imdbWhereIn}))");
      $stmt->bindValue(1, $userId);
      $pos = 1;
      foreach ($requestJson['movie_ids'] as $k => $id) {
        $pos++;
        $stmt->bindValue($pos, $id["id"]);
      }
      foreach ($requestJson['tmdb_ids'] as $k => $id) {
        $pos++;
        $stmt->bindValue($pos, $id["id"]);
      }
      foreach ($requestJson['imdb_ids'] as $k => $id) {
        $pos++;
        $stmt->bindValue($pos, $id["id"]);
      }
      $stmt->execute();
      $usersMovies = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $usersMovies[] = $row;
      }
      $response["message"] = $usersMovies;
      
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