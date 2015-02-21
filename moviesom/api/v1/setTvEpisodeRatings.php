<?php
  /**
   * Set tv ratings.
   * Expects JSON as payload I.e.:
   *  {
   *    "title": "Purple Giraffe",
   *    "tmdb_id": 62757,
   *    "tmdb_tv_id": "1100",
   *    "air_date": "2005-09-26",
   *    "season_number": 1,
   *    "episode_number": 2,
   *    "still_path": "/z0QxgVgljSBFMghlEpa9y8jR7Xz.jpg",
   *    "tmdb_rating": 0,
   *    "tmdb_votes": 0,
   *    "imdb_id": "tt0606111",
   *    "imdb_rating": "8.2",
   *    "imdb_votes": "1739"
   *  }
   */

  header('HTTP/1.1 500 Internal Server Error');
  header('Content-type: application/json');
  $response = [];
  $response['status'] = 500;
  
  $requestJson = json_decode(file_get_contents("php://input"), true);
  
  if (isset($requestJson['title']) && isset($requestJson['tmdb_id']) && 
      isset($requestJson['tmdb_tv_id']) && isset($requestJson['season_number']) && 
      isset($requestJson['episode_number']) && isset($requestJson['tmdb_rating']) &&
      isset($requestJson['tmdb_votes']) && isset($requestJson['imdb_id']) &&
      isset($requestJson['imdb_rating']) && isset($requestJson['imdb_votes'])) {
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }
      
      $stmt = $dbh->prepare("SELECT * FROM tv_episode_sources AS tes
                                JOIN tv_episodes AS te ON te.id=tes.tv_episode_id
                                JOIN tv_sources AS ts ON ts.tmdb_id=te.tmdb_tv_id
                                JOIN tv ON tv.id=ts.tv_id
                              WHERE tes.tmdb_id=:tmdb_id
                                AND tes.imdb_id=:imdb_id");
      $stmt->bindParam(":tmdb_id", $requestJson["tmdb_id"]);
      $stmt->bindParam(":imdb_id", $requestJson["imdb_id"]);
      $stmt->execute();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $movieExists = true;
        $tv_episode_id = $row["tv_episode_id"];
        break;
      }
      
      // We create the episode to obtain a episode id if it doesn't exist already.
      if(isset($tv_episode_id) === false) {
        // Insert record into tv
        $stmt = $dbh->prepare("INSERT INTO tv_episodes (title, air_date, tmdb_tv_id, season_number, episode_number, still_path) VALUES (:title, :air_date, :tmdb_tv_id, :season_number, :episode_number, :still_path)");
        $stmt->bindParam(":title", $requestJson["title"]);
        $stmt->bindParam(":air_date", $requestJson["air_date"]);
        $stmt->bindParam(":tmdb_tv_id", $requestJson["tmdb_tv_id"]);
        $stmt->bindParam(":season_number", $requestJson["season_number"]);
        $stmt->bindParam(":episode_number", $requestJson["episode_number"]);
        $stmt->bindParam(":still_path", $requestJson["still_path"]);
        $stmt->execute();
        $tv_episode_id = $dbh->lastInsertId();
      } else {
        $stmt = $dbh->prepare("UPDATE tv_episodes SET title=:title, air_date=:air_date, tmdb_tv_id=:tmdb_tv_id, season_number=:season_number, episode_number=:episode_number, still_path=:still_path WHERE id=:tv_episode_id");
        $stmt->bindParam(":tv_episode_id", $requestJson["tv_episode_id"]);
        $stmt->bindParam(":title", $requestJson["title"]);
        $stmt->bindParam(":air_date", $requestJson["air_date"]);
        $stmt->bindParam(":tmdb_tv_id", $requestJson["tmdb_tv_id"]);
        $stmt->bindParam(":season_number", $requestJson["season_number"]);
        $stmt->bindParam(":episode_number", $requestJson["episode_number"]);
        $stmt->bindParam(":still_path", $requestJson["still_path"]);
        $stmt->execute();
      }
      
      // Insert the ratings
      $stmt = $dbh->prepare(
        "INSERT INTO tv_episode_ratings (tv_episode_id, source_id, rating, votes)
        VALUES (:tv_episode_id, :tmdb_id, :tmdb_rating, :tmdb_votes),
          (:tv_episode_id, :imdb_id, :imdb_rating, :imdb_votes)
        ON DUPLICATE KEY UPDATE rating=VALUES(rating), votes=VALUES(votes), updated=now()"
      );
      $stmt->bindParam(":tv_episode_id", $tv_episode_id);
      $stmt->bindParam(":tmdb_id", $requestJson["tmdb_id"]);
      $stmt->bindParam(":imdb_id", $requestJson["imdb_id"]);
      $stmt->bindParam(":tmdb_rating", $requestJson["tmdb_rating"], PDO::PARAM_STR);
      $requestJson["imdb_rating"] = ($requestJson["imdb_rating"]) ? $requestJson["imdb_rating"] : 0;
      $stmt->bindParam(":imdb_rating", $requestJson["imdb_rating"], PDO::PARAM_STR);
      $stmt->bindParam(":tmdb_votes", $requestJson["tmdb_votes"], PDO::PARAM_INT);
      $requestJson["imdb_votes"] = ($requestJson["imdb_votes"]) ? $requestJson["imdb_votes"] : 0;
      $stmt->bindParam(":imdb_votes", $requestJson["imdb_votes"], PDO::PARAM_INT);
      $stmt->execute();

      // Insert the episode sources
      $stmt = $dbh->prepare(
        "INSERT INTO tv_episode_sources (tv_episode_id, tmdb_id, imdb_id) VALUES (:tv_episode_id, :tmdb_id, :imdb_id)" .
        " ON DUPLICATE KEY UPDATE tv_episode_id=VALUES(tv_episode_id), tmdb_id=VALUES(tmdb_id), imdb_id=VALUES(imdb_id)"
      );
      $stmt->bindParam(":tv_episode_id", $tv_episode_id);
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