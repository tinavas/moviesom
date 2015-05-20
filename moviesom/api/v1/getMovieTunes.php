<?php
  /**
   * Get movie tunes.
   * Expects JSON as payload I.e.:
   *  {
   *    "movie_ids": [
   *      {id: "1"},
   *      {id: "2"}
   *    ],
   *    "movie_tmdb_ids": [
   *      {id: "550"},
   *      {id: "289732"}
   *    ],
   *    "movie_imdb_ids": [
   *      {id: "tt0137523"},
   *      {id: "tt3560742"}
   *    ]
   *  }
   */

  function queryTuneFind($url) {
    $curl = curl_init($url);
    curl_setopt_array($curl, array(
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTPAUTH => CURLAUTH_ANY,
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_USERPWD => "4a9f4b9749e4f7f3f42cd7b97eabc0f3:88d12c3a872f2ba4903d976440b27aa1",
      CURLOPT_SSL_VERIFYPEER => false
    ));
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
  }

  header('HTTP/1.1 500 Internal Server Error');
  header('Content-type: application/json');
  $response = [];
  $response['status'] = 500;
  
  $requestJson = json_decode(file_get_contents("php://input"), true);
  
  // We need at least one of the following arrays in order to proceed with searching.
  if (isset($requestJson['movie_ids']) || isset($requestJson['movie_tmdb_ids']) || isset($requestJson['movie_imdb_ids'])) {
    // If any of the request variables aren't defined then we create an empty one.
    if(isset($requestJson['movie_ids']) == false) $requestJson['movie_ids'] = [];
    if(isset($requestJson['movie_tmdb_ids']) == false) $requestJson['movie_tmdb_ids'] = [];
    if(isset($requestJson['movie_imdb_ids']) == false) $requestJson['movie_imdb_ids'] = [];
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $idsWhereIn = (count($requestJson['movie_ids'])) ? implode(',', array_fill(0, count($requestJson['movie_ids']), '?')) : "";
      if(strlen($idsWhereIn) == 0) $idsWhereIn = "NULL";
      $tmdbWhereIn = (count($requestJson['movie_tmdb_ids'])) ? implode(',', array_fill(0, count($requestJson['movie_tmdb_ids']), '?')): "";
      if(strlen($tmdbWhereIn) == 0) $tmdbWhereIn = "NULL";
      $imdbWhereIn = (count($requestJson['movie_imdb_ids'])) ? implode(',', array_fill(0, count($requestJson['movie_imdb_ids']), '?')) : "";
      if(strlen($imdbWhereIn) == 0) $imdbWhereIn = "NULL";
      $stmt = $dbh->prepare("SELECT m.title, m.release_date, m.runtime FROM movies AS m
                              WHERE m.id 
                                IN(SELECT m.id FROM movies AS m 
                                      JOIN movie_sources AS ms ON ms.movie_id=m.id 
                                    WHERE m.id IN({$idsWhereIn}) OR ms.tmdb_id IN({$tmdbWhereIn}) OR ms.imdb_id IN({$imdbWhereIn}))");
      $pos = 0;
      foreach ($requestJson['movie_ids'] as $k => $id) {
        $pos++;
        $stmt->bindValue($pos, $id["id"]);
      }
      foreach ($requestJson['movie_tmdb_ids'] as $k => $id) {
        $pos++;
        $stmt->bindValue($pos, $id["id"]);
      }
      foreach ($requestJson['movie_imdb_ids'] as $k => $id) {
        $pos++;
        $stmt->bindValue($pos, $id["id"]);
      }
      $stmt->execute();
      $ratings = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row["tunefind_id"] = strtolower(str_ireplace(["(", ")", ":"], "", str_ireplace([" "], "-", $row["title"])) . "-" . substr($row["release_date"], 0, 4));
        $row["tunefind"] = json_decode(queryTuneFind("https://www.tunefind.com/api/v1/movie/{$row['tunefind_id']}"), true);
        if($row["tunefind"] == null) {
          $row["tunefind_id"] = strtolower(str_ireplace(["(", ")", ":"], "", str_ireplace([" "], "-", $row["title"])));
          $row["tunefind"] = json_decode(queryTuneFind("https://www.tunefind.com/api/v1/movie/{$row['tunefind_id']}"), true);
        }
        
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