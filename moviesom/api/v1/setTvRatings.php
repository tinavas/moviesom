<?php
  /**
   * Set tv ratings.
   * Expects JSON as payload I.e.:
   *  {
   *    "title": "Fight Club"
   *    "episode_run_time": "22, 25",
   *    "tmdb_id": 7468,
   *    "first_air_date": "1999-10-14",
   *    "last_air_date": "2014-03-31",
   *    "number_of_episodes": 208,
   *    "number_of_seasons": 9,
   *    "backdrop_path": "/nS0rEXPbkHI449SF6R4WUQvTVxE.jpg",
   *    "poster_path": "/rLTdj7oB9oxsYwuweeglWRzRng7.jpg",
   *    "tmdb_rating": 6,
   *    "tmdb_votes": 1000,
   *    "imdb_id": "tt0137523",
   *    "imdb_rating": 7,
   *    "imdb_votes": 2000
   *  }
   */



  header('HTTP/1.1 500 Internal Server Error');
  header('Content-type: application/json');
  $response = [];
  $response['status'] = 500;
  
  $requestJson = json_decode(file_get_contents("php://input"), true);

  if (isset($requestJson['title']) && isset($requestJson['episode_run_time']) && 
      isset($requestJson['first_air_date']) && isset($requestJson['last_air_date']) && 
      isset($requestJson['number_of_episodes']) && isset($requestJson['number_of_seasons']) && 
      isset($requestJson['backdrop_path']) && isset($requestJson['poster_path']) &&
      isset($requestJson['tmdb_id']) && isset($requestJson['imdb_id']) &&
      isset($requestJson['tmdb_rating']) && isset($requestJson['imdb_rating']) && 
      isset($requestJson['tmdb_votes']) && isset($requestJson['imdb_votes'])) {
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }
      
      $stmt = $dbh->prepare("SELECT * FROM tv_ratings WHERE tv_id=(SELECT m.id FROM tv AS m JOIN tv_sources AS ms ON ms.tv_id=m.id WHERE m.id=:id OR ms.tmdb_id=:tmdb_id OR ms.imdb_id=:imdb_id GROUP BY m.id LIMIT 1)");
      $stmt->bindParam(":id", $requestJson["id"]);
      $stmt->bindParam(":tmdb_id", $requestJson["tmdb_id"]);
      $stmt->bindParam(":imdb_id", $requestJson["imdb_id"]);
      $stmt->execute();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $movieExists = true;
        $tv_id = $row["tv_id"];
        break;
      }

      $episodeRunTime = implode(",", $requestJson["episode_run_time"]);
      
      // We create the movie to obtain a movie id if it doesn't exist already.
      if(isset($tv_id) === false) {
        // Insert record into tv
        $stmt = $dbh->prepare("INSERT INTO tv (title, episode_run_time, number_of_episodes, number_of_seasons, first_air_date, last_air_date, backdrop_path, poster_path) VALUES (:title, :episode_run_time, :number_of_episodes, :number_of_seasons, :first_air_date, :last_air_date, :backdrop_path, :poster_path)");
        $stmt->bindParam(":title", $requestJson["title"]);
        $stmt->bindParam(":episode_run_time", $episodeRunTime);
        $stmt->bindParam(":number_of_episodes", $requestJson["number_of_episodes"]);
        $stmt->bindParam(":number_of_seasons", $requestJson["number_of_seasons"]);
        $stmt->bindParam(":first_air_date", $requestJson["first_air_date"]);
        $stmt->bindParam(":last_air_date", $requestJson["last_air_date"]);
        $stmt->bindParam(":backdrop_path", $requestJson["backdrop_path"]);
        $stmt->bindParam(":poster_path", $requestJson["poster_path"]);
        $stmt->execute();
        $tv_id = $dbh->lastInsertId();
      } else {
        $stmt = $dbh->prepare("UPDATE tv SET episode_run_time=:episode_run_time, number_of_episodes=:number_of_episodes, number_of_seasons=:number_of_seasons, first_air_date=:first_air_date, last_air_date=:last_air_date, backdrop_path=:backdrop_path, poster_path=:poster_path WHERE id=:tv_id");
        $stmt->bindParam(":episode_run_time", $episodeRunTime);
        $stmt->bindParam(":number_of_episodes", $requestJson["number_of_episodes"]);
        $stmt->bindParam(":number_of_seasons", $requestJson["number_of_seasons"]);
        $stmt->bindParam(":first_air_date", $requestJson["first_air_date"]);
        $stmt->bindParam(":last_air_date", $requestJson["last_air_date"]);
        $stmt->bindParam(":tv_id", $tv_id);
        $stmt->bindParam(":backdrop_path", $requestJson["backdrop_path"]);
        $stmt->bindParam(":poster_path", $requestJson["poster_path"]);
        $stmt->execute();
      }
      
      // Insert the ratings
      $stmt = $dbh->prepare(
        "INSERT INTO tv_ratings (tv_id, source_id, rating, votes)" .
        " VALUES (:tv_id, :tmdb_id, :tmdb_rating, :tmdb_votes)," .
        " (:tv_id, :imdb_id, :imdb_rating, :imdb_votes)" .
        " ON DUPLICATE KEY UPDATE rating=VALUES(rating), votes=VALUES(votes)"
      );
      $stmt->bindParam(":tv_id", $tv_id);
      $stmt->bindParam(":tmdb_id", $requestJson["tmdb_id"]);
      $stmt->bindParam(":imdb_id", $requestJson["imdb_id"]);
      $stmt->bindParam(":tmdb_rating", $requestJson["tmdb_rating"], PDO::PARAM_STR);
      $requestJson["imdb_rating"] = ($requestJson["imdb_rating"]) ? $requestJson["imdb_rating"] : 0;
      $stmt->bindParam(":imdb_rating", $requestJson["imdb_rating"], PDO::PARAM_STR);
      $stmt->bindParam(":tmdb_votes", $requestJson["tmdb_votes"], PDO::PARAM_INT);
      $requestJson["imdb_votes"] = ($requestJson["imdb_votes"]) ? $requestJson["imdb_rating"] : 0;
      $stmt->bindParam(":imdb_votes", $requestJson["imdb_votes"], PDO::PARAM_INT);
      $stmt->execute();

      // Insert the movie sources
      $stmt = $dbh->prepare(
        "INSERT INTO tv_sources (tv_id, tmdb_id, imdb_id) VALUES (:tv_id, :tmdb_id, :imdb_id)" .
        " ON DUPLICATE KEY UPDATE tv_id=VALUES(tv_id), tmdb_id=VALUES(tmdb_id), imdb_id=VALUES(imdb_id)"
      );
      $stmt->bindParam(":tv_id", $tv_id);
      $stmt->bindParam(":tmdb_id", $requestJson["tmdb_id"]);
      $stmt->bindParam(":imdb_id", $requestJson["imdb_id"]);
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