<?php
  /**
   * Get users movies list.
   * Expects JSON as payload I.e.:
   *  {
   *    "token": "d967a19940bdc4d498d0420a9fb12802ab5857a0a634ab73ae8984c5cf46ab3f9322dd5c1c3f069cc9d226ce47112747976c289cf6ae7b41a8ac72a7dc69c83f"
   *    "watched_filter": "true",
   *    "blu_ray_filter": "false",
   *    "dvd_filter": "false",
   *    "digital_filter": "false",
   *    "other_filter": "false",
   *    "lend_out_filter": "false",
   *    "page": 0,
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

      $defaultFilter = "(watched>0 OR blu_ray>0 OR dvd>0 OR digital>0 OR other>0 OR recommend>0)";
      
      $searchString = "%%";
      if(isset($requestJson["query"])) {
        $searchString .= "%{$requestJson["query"]}%";
      }
      
      /**
       * FILTER
       */
      $filterString = "";
      if(isset($requestJson["watched_filter"]) && strcasecmp($requestJson["watched_filter"], "true") == 0) {
        $defaultFilter = "";
        $filterString .= "OR watched>0 ";
      }
      
      if(isset($requestJson["blu_ray_filter"]) && strcasecmp($requestJson["blu_ray_filter"], "true") == 0) {
        $defaultFilter = "";
        $filterString .= "OR blu_ray>0 ";
      }
      
      if(isset($requestJson["dvd_filter"]) && strcasecmp($requestJson["dvd_filter"], "true") == 0) {
        $defaultFilter = "";
        $filterString .= "OR dvd>0 ";
      }
      
      if(isset($requestJson["digital_filter"]) && strcasecmp($requestJson["digital_filter"], "true") == 0) {
        $defaultFilter = "";
        $filterString .= "OR digital>0 ";
      }
      
      if(isset($requestJson["other_filter"]) && strcasecmp($requestJson["other_filter"], "true") == 0) {
        $defaultFilter = "";
        $filterString .= "OR other>0 ";
      }
      
      if(isset($requestJson["lend_out_filter"]) && strcasecmp($requestJson["lend_out_filter"], "true") == 0) {
        $defaultFilter = "";
        $filterString .= "OR CHAR_LENGTH(lend_out)>0 ";
      }
      
      if(isset($requestJson["recommend_filter"]) && strcasecmp($requestJson["recommend_filter"], "true") == 0) {
        $defaultFilter = "";
        $filterString .= "OR recommend>0 ";
      }
      
      $filterString .= $defaultFilter;
      $OR = "OR";
      if(strpos($filterString, "OR") == 0) {
        $filterString = substr( $filterString, 0, strpos( $filterString, $OR)) . "" . substr( $filterString, strpos( $filterString, $OR) + strlen( $OR));
      }
      
      /**
       * SORT
       * When recommend filter is turned on the date sorting work different than normal.
       */
      $sortString = "";
      if(isset($requestJson["sort"])) {
        $defaultSort = "";
        switch($requestJson["sort"]) {
          case "added":
            if(isset($requestJson["recommend_filter"]) && strcasecmp($requestJson["recommend_filter"], "true") == 0) {
              $sortString .= "ORDER BY recommend_date ASC";
            } else {
              $sortString .= "ORDER BY added DESC";
            }
            break;
          case "updated":
            if(isset($requestJson["recommend_filter"]) && strcasecmp($requestJson["recommend_filter"], "true") == 0) {
              $sortString .= "ORDER BY recommend_date DESC";
            } else {
              $sortString .= "ORDER BY user_updated DESC";
            }
            break;
          default:
            $sortString = "ORDER BY title";
            break;
        }
      }
      
      
      /**
       * SEARCH
       */
      // Get total count of movies and tv series
      $stmt = $dbh->prepare("SELECT COUNT(*) AS total_results 
                              FROM 
                              (SELECT m.id
                              FROM
                              users_movies AS um 
                                JOIN movies AS m ON m.id=um.movie_id
                              WHERE um.user_id=:user_id
                                AND (m.title LIKE :search_title OR m.original_title LIKE :search_title)
                                AND (
                                  {$filterString}
                                )
                              UNION ALL
                              SELECT te.id
                              FROM
                              users_tv_episodes AS ute 
                                JOIN tv_episodes AS te ON ute.tv_episode_id=te.id
                                JOIN tv_sources AS ts ON ts.tmdb_id=te.tmdb_tv_id
                                JOIN tv ON tv.id=ts.tv_id
                              WHERE ute.user_id=:user_id
                                AND (tv.title LIKE :search_title OR te.title LIKE :search_title)
                                AND (
                                  {$filterString}
                                )
                              ) subquery");
      $stmt->bindParam(":user_id", $userId);
      $stmt->bindParam(":search_title", $searchString, PDO::PARAM_STR);
      $stmt->execute();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $response['total_results'] = intval($row["total_results"]);
        $response['total_pages'] = intval(ceil($row["total_results"]/$resultsPerPage));
      }
      
      // SELECT movies and tv series
      $stmt = $dbh->prepare("SELECT * FROM
                              (SELECT 
                                m.id, m.title, m.runtime, '' AS number_of_episodes, '' as number_of_seasons, release_date, '' AS last_air_date,
                                backdrop_path, poster_path, '' AS episode_title, '' AS season_number, '' AS episode_number, '' AS air_date,
                                um.tmdb_id, mr.rating, mr.votes, mr.updated, um.imdb_id,
                                um.watched, um.want_to_watch, um.blu_ray, um.dvd, um.digital, um.other, um.lend_out, um.recommend, 
                                um.added, um.updated AS user_updated, rm.added AS recommend_date,
                                'movie' AS media_type
                              FROM movie_ratings AS mr
                                JOIN movies AS m ON m.id=mr.movie_id
                                JOIN users_movies AS um ON um.movie_id=m.id
                                LEFT JOIN recommend_movies AS rm ON rm.tmdb_id=um.tmdb_id
                              WHERE um.user_id=:user_id 
                                AND (m.title LIKE :search_title OR m.original_title LIKE :search_title)
                                AND (
                                  {$filterString}
                                )
                                AND um.tmdb_id=mr.source_id
                                GROUP BY m.id
                              UNION ALL
                              SELECT 
                                tv.id AS tv_id, tv.title, tv.episode_run_time AS runtime, tv.number_of_episodes, tv.number_of_seasons,
                                tv.first_air_date, tv.last_air_date, tv.backdrop_path, tv.poster_path, te.title AS episode_title,
                                te.season_number, te.episode_number, te.air_date, te.tmdb_tv_id AS tmdb_id, ter.rating, ter.votes, ter.updated, ute.imdb_id,
                                ute.watched, ute.want_to_watch, ute.blu_ray, ute.dvd, ute.digital, ute.other, ute.lend_out, ute.recommend, 
                                ute.added, ute.updated AS user_updated, null AS recommend_date,
                                'tv' AS media_type
                              FROM users_tv_episodes AS ute
                                JOIN tv_episode_sources AS tes ON tes.tmdb_id=ute.tmdb_id
                                JOIN tv_episodes AS te ON te.id=tes.tv_episode_id
                                JOIN tv_episode_ratings AS ter ON ter.tv_episode_id=te.id
                                JOIN tv_sources AS ts ON ts.tmdb_id=te.tmdb_tv_id
                                JOIN tv ON tv.id=ts.tv_id
                              WHERE ute.user_id=:user_id
                                AND (tv.title LIKE :search_title OR tv.original_title LIKE :search_title OR te.title LIKE :search_title )
                                AND (
                                  {$filterString}
                                )
                                AND ter.source_id=tes.tmdb_id
                              GROUP BY tv.id) subquery
                              {$sortString}
                            LIMIT :offset, :results_per_page");
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
        $row["owned"] = $row["blu_ray"]|$row["dvd"]|$row["digital"]|$row["other"];
        $usersMovies[] = $row;
      }
      
      // Set the response results.
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