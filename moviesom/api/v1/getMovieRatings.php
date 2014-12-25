<?php
  /**
   * Get movie ratings.
   * Expects JSON as payload I.e.:
   *  {
   *    "id": 1234,
   *    "tmdb_id": "7468",
   *    "tmdb_id": "tt0137523"
   *  }
   */



  header('HTTP/1.1 500 Internal Server Error');
  header('Content-type: application/json');
  $response = [];
  $response['status'] = 500;
  
  $requestJson = json_decode(file_get_contents("php://input"), true);
  
  if (isset($requestJson['id']) || isset($requestJson['tmdb_id']) || isset($requestJson['imdb_id'])) {
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }
      $stmt = $dbh->prepare("SELECT * FROM movie_ratings WHERE movie_id=(SELECT m.id FROM movies AS m JOIN movie_sources AS ms ON ms.movie_id=m.id WHERE m.id=:id OR ms.tmdb_id=:tmdb_id OR ms.imdb_id=:imdb_id GROUP BY m.id LIMIT 1)");
      $stmt->bindParam(":id", $requestJson["id"]);
      $stmt->bindParam(":tmdb_id", $requestJson["tmdb_id"]);
      $stmt->bindParam(":imdb_id", $requestJson["imdb_id"]);
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
  
  $response['execTime'] = $execTime->getTime();
  echo json_encode($response);
  
?>