<?php
  /**
   * Get user tv episodes settings.
   * Expects JSON as payload I.e.:
   *  {
   *    "token": "d967a19940bdc4d498d0420a9fb12802ab5857a0a634ab73ae8984c5cf46ab3f9322dd5c1c3f069cc9d226ce47112747976c289cf6ae7b41a8ac72a7dc69c83f",
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
   *    ],
   *    "tv_tmdb_ids": [
   *      {id: "1100"},
   *      {id: "1425"}
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
  } else if (isset($requestJson['tv_episode_ids']) || 
              isset($requestJson['tv_episode_tmdb_ids']) || 
              isset($requestJson['tv_episode_imdb_ids']) || 
              isset($requestJson['tv_tmdb_ids'])) {
    // If any of the request variables aren't defined then we create an empty one.
    if(isset($requestJson['tv_episode_ids']) == false) $requestJson['tv_episode_ids'] = [];
    if(isset($requestJson['tv_episode_tmdb_ids']) == false) $requestJson['tv_episode_tmdb_ids'] = [];
    if(isset($requestJson['tv_episode_imdb_ids']) == false) $requestJson['tv_episode_imdb_ids'] = [];
    if(isset($requestJson['tv_tmdb_ids']) == false) $requestJson['tv_tmdb_ids'] = [];
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $tvEpisodeWhereIn = (count($requestJson['tv_episode_ids'])) ? implode(',', array_fill(0, count($requestJson['tv_episode_ids']), '?')) : "";
      if(strlen($tvEpisodeWhereIn) == 0) $tvEpisodeWhereIn = "NULL";
      $tvEpisodetvTmdbWhereIn = (count($requestJson['tv_episode_tmdb_ids'])) ? implode(',', array_fill(0, count($requestJson['tv_episode_tmdb_ids']), '?')): "";
      if(strlen($tvEpisodetvTmdbWhereIn) == 0) $tvEpisodetvTmdbWhereIn = "NULL";
      $tvEpisodeImdbWhereIn = (count($requestJson['tv_episode_imdb_ids'])) ? implode(',', array_fill(0, count($requestJson['tv_episode_imdb_ids']), '?')) : "";
      if(strlen($tvEpisodeImdbWhereIn) == 0) $tvEpisodeImdbWhereIn = "NULL";
      $tvTmdbWhereIn = (count($requestJson['tv_tmdb_ids'])) ? implode(',', array_fill(0, count($requestJson['tv_tmdb_ids']), '?')): "";
      if(strlen($tvTmdbWhereIn) == 0) $tvTmdbWhereIn = "NULL";
      $stmt = $dbh->prepare("SELECT *, ute.tmdb_id AS episode_tmdb_id, ute.imdb_id AS episode_imdb_id FROM users_tv_episodes AS ute
                                JOIN tv_episodes AS te ON te.id=ute.tv_episode_id
                                JOIN tv_sources AS ts ON ts.tmdb_id=te.tmdb_tv_id
                                JOIN tv ON tv.id=ts.tv_id
                              WHERE 
                                ute.user_id=? 
                                AND (ute.tv_episode_id IN({$tvEpisodeWhereIn}) 
                                  OR ute.tmdb_id IN({$tvEpisodetvTmdbWhereIn}) 
                                  OR ute.imdb_id IN({$tvEpisodeImdbWhereIn}) 
                                  OR te.tmdb_tv_id IN({$tvTmdbWhereIn}))");
      $stmt->bindValue(1, $userId);
      $pos = 1;
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
      foreach ($requestJson['tv_tmdb_ids'] as $k => $id) {
        $pos++;
        $stmt->bindValue($pos, $id["id"]);
      }
      $stmt->execute();
      $usersMovies = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $usersMovies[] = $row;
      }
      $response["message"] = $usersMovies;
      
      header('HTTP/1.1 200 OK');
      $response['status'] = 200;
    }
    catch(PDOException $e) {  
      $response['message'] = $e;
    }
  }
  
?>