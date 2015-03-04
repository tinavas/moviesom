<?php
  /**
   * Get movie statistics.
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
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $stmt = $dbh->prepare("SELECT SUM(times_watched) AS movies_seen,
                                SUM(time_watching_movies) AS movies_seen_runtime,
                                SUM(seen) AS unique_movies_seen,
                                SUM(movie_runtime) AS unique_movies_seen_runtime,
                                SUM(owned) AS owned_movies,
                                SUM(runtime*owned) AS owned_movies_runtime,
                                SUM(want_to_watch) AS want_to_watch,
                                SUM(runtime*want_to_watch) AS want_to_watch_runtime
                              FROM (SELECT runtime,
                                want_to_watch,
                                watched AS times_watched,
                                LEAST(watched, 1) AS seen,
                                (runtime*LEAST(watched, 1)) AS movie_runtime,
                                (runtime*watched) AS time_watching_movies,
                                GREATEST(blu_ray,dvd,digital,other) AS owned
                                FROM movies AS m 
                                  JOIN users_movies AS um ON um.movie_id=m.id 
                                WHERE um.user_id=:user_id) AS umm");
      $stmt->bindParam(":user_id", $userId);
      $stmt->execute();
      $userStats = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $userStats["movie_stats"] = $row;
      }
      
      $stmt = $dbh->prepare("SELECT g.genre, COUNT(g.id) AS count 
                              FROM genres AS g 
                                JOIN movie_genres AS mg ON mg.genre_tmdb_id=g.tmdb_id
                                JOIN movies AS m ON m.id=mg.movie_id
                                JOIN users_movies AS um ON um.movie_id=m.id
                              WHERE um.user_id=18 AND um.watched>0
                              GROUP BY g.id");
      $stmt->bindParam(":user_id", $userId);
      $stmt->execute();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $userStats["movie_genres_stats"] = $row;
      }
      
      $stmt = $dbh->prepare("SELECT SUM(times_watched) AS episodes_seen,
                                SUM(time_watching_episodes) AS episodes_seen_runtime,
                                SUM(seen) AS unique_episodes_seen,
                                SUM(episode_runtime) AS unique_episodes_seen_runtime,
                                SUM(owned) AS owned_episodes,
                                SUM(runtime*owned) AS owned_episodes_runtime
                              FROM (SELECT tv.episode_run_time AS runtime, 
                                    watched AS times_watched, 
                                    LEAST(watched, 1) AS seen,
                                    (tv.episode_run_time*LEAST(watched, 1)) AS episode_runtime,
                                    (tv.episode_run_time*watched) AS time_watching_episodes,
                                    GREATEST(blu_ray,dvd,digital,other) AS owned
                                  FROM users_tv_episodes AS ute
                                    JOIN tv_episodes AS te ON te.id=ute.tv_episode_id
                                    JOIN tv_sources AS ts ON ts.tmdb_id=te.tmdb_tv_id
                                    JOIN tv ON tv.id=ts.tv_id
                                  WHERE ute.user_id=:user_id) AS tvstats");
      $stmt->bindParam(":user_id", $userId);
      $stmt->execute();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $userStats["tv_stats"] = $row;
      }
      
      
      $response["message"] = $userStats;
      header('HTTP/1.1 200 OK');
      $response['status'] = 200;
    }
    catch(PDOException $e) {  
      $response['message'] = $e;
    }
  }
  
?>