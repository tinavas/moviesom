<?php
  /**
   * Get users bookmarks list.
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
      $stmt = $dbh->prepare("SELECT COUNT(*) AS total_results 
                              FROM 
                              (SELECT m.id
                              FROM
                              users_movies AS um 
                                JOIN movies AS m ON m.id=um.movie_id
                              WHERE um.user_id=:user_id
                                AND (m.title LIKE :search_title 
                                  OR m.original_title LIKE :search_title 
                                  OR um.movie_id=
                                    (SELECT movie_id FROM movie_alternative_titles WHERE title LIKE :search_title))
                                AND want_to_watch>0
                              UNION ALL
                              SELECT te.id
                              FROM
                              users_tv_episodes AS ute 
                                JOIN tv_episodes AS te ON ute.tv_episode_id=te.id
                                JOIN tv_sources AS ts ON ts.tmdb_id=te.tmdb_tv_id
                                JOIN tv ON tv.id=ts.tv_id
                              WHERE ute.user_id=:user_id
                                AND (tv.title LIKE :search_title OR te.title LIKE :search_title)
                                AND want_to_watch>0) subquery");
      $stmt->bindParam(":user_id", $userId);
      $stmt->bindParam(":search_title", $searchString, PDO::PARAM_STR);
      $stmt->execute();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $response['total_results'] = intval($row["total_results"]);
        $response['total_pages'] = intval(ceil($row["total_results"]/$resultsPerPage));
      }
      $stmt = $dbh->prepare("SELECT * FROM
                              (SELECT 
                                m.id, m.title, m.runtime, '' AS number_of_episodes, '' as number_of_seasons, release_date, '' AS last_air_date, 
                                backdrop_path, poster_path, '' AS episode_title, '' AS season_number, '' AS episode_number, '' AS air_date, 
                                um.tmdb_id, mr.rating, mr.votes, mr.updated, um.imdb_id,
                                um.watched, um.want_to_watch, um.blu_ray, um.dvd, um.digital, um.other, um.lend_out,
                                '' AS episode_tmdb_id, '' AS episode_imdb_id,
                                'movie' AS media_type
                              FROM movie_ratings AS mr
                                JOIN movies AS m ON m.id=mr.movie_id
                                JOIN users_movies AS um ON um.movie_id=m.id
                              WHERE um.user_id=:user_id AND (m.title LIKE :search_title
                                  OR m.original_title LIKE :search_title 
                                  OR m.id=(SELECT movie_id FROM movie_alternative_titles WHERE title LIKE :search_title))
                                AND want_to_watch>0
                                AND um.tmdb_id=mr.source_id
                              UNION ALL
                              SELECT 
                                m.id, m.title, m.episode_run_time AS runtime, '' AS number_of_episodes, '' as number_of_seasons, m.first_air_date, m.last_air_date, 
                                backdrop_path, poster_path, '' AS episode_title, '' AS season_number, '' AS episode_number, '' AS air_date, 
                                um.tmdb_id, mr.rating, mr.votes, mr.updated, um.imdb_id,
                                um.watched, um.want_to_watch, um.blu_ray, um.dvd, um.digital, um.other, um.lend_out,
                                '' AS episode_tmdb_id, '' AS episode_imdb_id,
                                'tv' AS media_type
                              FROM tv_ratings AS mr
                                JOIN tv AS m ON m.id=mr.tv_id
                                JOIN users_tv AS um ON um.tv_id=m.id
                              WHERE um.user_id=:user_id AND m.title LIKE :search_title
                                AND want_to_watch>0
                                AND um.tmdb_id=mr.source_id
                              UNION ALL
                              SELECT 
                                tv.id AS tv_id, tv.title, tv.episode_run_time AS runtime, tv.number_of_episodes, tv.number_of_seasons, 
                                tv.first_air_date, tv.last_air_date, tv.backdrop_path, tv.poster_path, te.title AS episode_title,
                                te.season_number, te.episode_number, te.air_date, te.tmdb_tv_id AS tmdb_id, ter.rating, ter.votes, ter.updated, ute.imdb_id,
                                ute.watched, ute.want_to_watch, ute.blu_ray, ute.dvd, ute.digital, ute.other, ute.lend_out,
                                tes.tmdb_id AS episode_tmdb_id, tes.imdb_id AS episode_imdb_id,
                                'tv' AS media_type
                              FROM users_tv_episodes AS ute
                                JOIN tv_episode_sources AS tes ON tes.tmdb_id=ute.tmdb_id
                                JOIN tv_episodes AS te ON te.id=tes.tv_episode_id
                                JOIN tv_episode_ratings AS ter ON ter.tv_episode_id=te.id
                                JOIN tv_sources AS ts ON ts.tmdb_id=te.tmdb_tv_id
                                JOIN tv ON tv.id=ts.tv_id
                              WHERE ute.user_id=:user_id
                                AND (tv.title LIKE :search_title OR te.title LIKE :search_title )
                                AND want_to_watch>0
                                AND ter.source_id=tes.tmdb_id) subquery
                            ORDER BY title LIMIT :offset, :results_per_page");
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
        $row["vote_average"] = $row["rating"];
        $row["vote_count"] = $row["votes"];
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