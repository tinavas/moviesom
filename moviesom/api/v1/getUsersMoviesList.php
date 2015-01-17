<?php
  /**
   * Get users movies list.
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
      $page = 1;
      if(isset($requestJson['page'])) {
        $page = intval($requestJson['page']);
      }
      $response['page'] = $page;

      $resultsPerPage = 20;
    
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }

      $searchString = "%%";
      if(isset($requestJson["query"])) {
        $searchString = "%{$requestJson["query"]}%";
      }
      
      // Get total count
      $stmt = $dbh->prepare("SELECT COUNT(*) AS total_results FROM users_movies AS um JOIN movies AS m ON m.id=um.movie_id WHERE um.user_id=:user_id AND m.title LIKE :search_title AND (watched>0 OR blu_ray>0 OR dvd>0 OR digital>0 OR other>0)");
      $stmt->bindParam(":user_id", $userId);
      $stmt->bindParam(":search_title", $searchString, PDO::PARAM_STR);
      $stmt->execute();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $response['total_results'] = intval($row["total_results"]);
        $response['total_pages'] = intval(ceil($row["total_results"]/$resultsPerPage));
      }
      $stmt = $dbh->prepare("SELECT * FROM movie_ratings AS mr JOIN movies AS m ON m.id=mr.movie_id JOIN users_movies AS um ON um.movie_id=m.id WHERE um.user_id=:user_id AND m.title LIKE :search_title AND (watched>0 OR blu_ray>0 OR dvd>0 OR digital>0 OR other>0) AND um.tmdb_id=mr.source_id ORDER BY m.title LIMIT :offset, :results_per_page");
      $stmt->bindParam(":user_id", $userId);
      $stmt->bindParam(":search_title", $searchString, PDO::PARAM_STR);
      $offset = (($page-1)*$resultsPerPage);
      $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
      $stmt->bindParam(":results_per_page", $resultsPerPage, PDO::PARAM_INT);
      $stmt->execute();
      $usersMovies = [];
      
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row["adult"] = false;
        $row["original_title"] = null;
        $row["popularity"] = null;
        $row["video"] = false;
        $row["id"] = $row["tmdb_id"];
        $row["media_type"] = "movie";
        $row["vote_average"] = $row["rating"];
        $row["vote_count"] = $row["votes"];
        $row["owned"] = $row["blu_ray"]|$row["dvd"]|$row["digital"]|$row["other"];
        $usersMovies[] = $row;
      }
      $response["results"] = $usersMovies;
      
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