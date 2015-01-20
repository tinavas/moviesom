<?php
  /**
   * Set movie ratings.
   * Expects JSON as payload I.e.:
   *  {
   *    "title": "Fight Club"
   *    "runtime": 139,
   *    "tmdb_id": 7468,
   *    "release_date": "1999-10-14",
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

  if (isset($requestJson['title']) && isset($requestJson['runtime']) && isset($requestJson['release_date']) && 
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
      
      $stmt = $dbh->prepare("SELECT * FROM movie_ratings WHERE movie_id=(SELECT m.id FROM movies AS m JOIN movie_sources AS ms ON ms.movie_id=m.id WHERE m.id=:id OR ms.tmdb_id=:tmdb_id OR ms.imdb_id=:imdb_id GROUP BY m.id LIMIT 1)");
      $stmt->bindParam(":id", $requestJson["id"]);
      $stmt->bindParam(":tmdb_id", $requestJson["tmdb_id"]);
      $stmt->bindParam(":imdb_id", $requestJson["imdb_id"]);
      $stmt->execute();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $movieExists = true;
        $movie_id = $row["movie_id"];
        break;
      }

      // We create the movie to obtain a movie id if it doesn't exist already.
      if(isset($movie_id) === false) {
        // Insert record into movies
        $stmt = $dbh->prepare("INSERT INTO movies (title, runtime, release_date, backdrop_path, poster_path) VALUES (:title, :runtime, :release_date, :backdrop_path, :poster_path)");
        $stmt->bindParam(":title", $requestJson["title"]);
        $stmt->bindParam(":runtime", $requestJson["runtime"]);
        $stmt->bindParam(":release_date", $requestJson["release_date"]);
        $stmt->bindParam(":backdrop_path", $requestJson["backdrop_path"]);
        $stmt->bindParam(":poster_path", $requestJson["poster_path"]);
        $stmt->execute();
        $movie_id = $dbh->lastInsertId();
      } else {
        $stmt = $dbh->prepare("UPDATE movies SET runtime=:runtime, release_date=:release_date, backdrop_path=:backdrop_path, poster_path=:poster_path WHERE id=:movie_id");
        $stmt->bindParam(":runtime", $requestJson["runtime"]);
        $stmt->bindParam(":release_date", $requestJson["release_date"]);
        $stmt->bindParam(":movie_id", $movie_id);
        $stmt->bindParam(":backdrop_path", $requestJson["backdrop_path"]);
        $stmt->bindParam(":poster_path", $requestJson["poster_path"]);
        $stmt->execute();
      }
      
      // Insert the ratings
      $stmt = $dbh->prepare(
        "INSERT INTO movie_ratings (movie_id, source_id, rating, votes)
        VALUES (:movie_id, :tmdb_id, :tmdb_rating, :tmdb_votes),
          (:movie_id, :imdb_id, :imdb_rating, :imdb_votes)
        ON DUPLICATE KEY UPDATE rating=VALUES(rating), votes=VALUES(votes), updated=now()");
      $stmt->bindParam(":movie_id", $movie_id);
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
        "INSERT INTO movie_sources (movie_id, tmdb_id, imdb_id) VALUES (:movie_id, :tmdb_id, :imdb_id)" .
        " ON DUPLICATE KEY UPDATE movie_id=VALUES(movie_id), tmdb_id=VALUES(tmdb_id), imdb_id=VALUES(imdb_id)"
      );
      $stmt->bindParam(":movie_id", $movie_id);
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