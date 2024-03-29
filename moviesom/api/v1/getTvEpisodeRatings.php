<?php
  /**
   * Get tv ratings.
   * Expects JSON as payload I.e.:
   *  {
   *    "tv_episode_ids": [
   *      {id: "1"},
   *      {id: "2"}
   *    ],
   *    "tv_episode_tmdb_ids": [
   *      {id: "550"},
   *      {id: "289732"}
   *    ],
   *    "tv_episode_imdb_ids": [
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
  
  // We need at least one of the following arrays in order to proceed with searching.
  if (isset($requestJson['tv_episode_ids']) || isset($requestJson['tv_episode_tmdb_ids']) || isset($requestJson['tv_episode_imdb_ids'])) {
    // If any of the request variables aren't defined then we create an empty one.
    if(isset($requestJson['tv_episode_ids']) == false) $requestJson['tv_episode_ids'] = [];
    if(isset($requestJson['tv_episode_tmdb_ids']) == false) $requestJson['tv_episode_tmdb_ids'] = [];
    if(isset($requestJson['tv_episode_imdb_ids']) == false) $requestJson['tv_episode_imdb_ids'] = [];
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $idsWhereIn = (count($requestJson['tv_episode_ids'])) ? implode(',', array_fill(0, count($requestJson['tv_episode_ids']), '?')) : "";
      if(strlen($idsWhereIn) == 0) $idsWhereIn = "NULL";
      $tmdbWhereIn = (count($requestJson['tv_episode_tmdb_ids'])) ? implode(',', array_fill(0, count($requestJson['tv_episode_tmdb_ids']), '?')): "";
      if(strlen($tmdbWhereIn) == 0) $tmdbWhereIn = "NULL";
      $imdbWhereIn = (count($requestJson['tv_episode_imdb_ids'])) ? implode(',', array_fill(0, count($requestJson['tv_episode_imdb_ids']), '?')) : "";
      if(strlen($imdbWhereIn) == 0) $imdbWhereIn = "NULL";
      $stmt = $dbh->prepare("SELECT ter.*, tes.*, tv.episode_run_time AS runtime FROM tv_episode_ratings AS ter 
                                JOIN tv_episode_sources AS tes ON tes.tv_episode_id=ter.tv_episode_id 
                                JOIN tv_episodes AS te1 ON te1.id=ter.tv_episode_id 
                                JOIN tv_sources AS ts ON ts.tmdb_id=te1.tmdb_tv_id 
                                JOIN tv ON tv.id=ts.tv_id 
                              WHERE ter.tv_episode_id 
                                IN(SELECT te.id FROM tv_episodes AS te 
                                      JOIN tv_episode_sources AS tes ON tes.tv_episode_id=te.id 
                                    WHERE te.id IN({$idsWhereIn}) OR tes.tmdb_id IN({$tmdbWhereIn}) OR tes.imdb_id IN({$imdbWhereIn}))");
      $pos = 0;
      foreach ($requestJson['tv_episode_ids'] as $k => $id) {
        $pos++;
        $stmt->bindValue($pos, $id["id"]);
      }
      foreach ($requestJson['tv_episode_tmdb_ids'] as $k => $id) {
        $pos++;
        $stmt->bindValue($pos, $id["id"]);
      }
      foreach ($requestJson['tv_episode_imdb_ids'] as $k => $id) {
        $pos++;
        $stmt->bindValue($pos, $id["id"]);
      }
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