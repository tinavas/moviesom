<?php
  /**
   * Recommend movie to users.
   * Expects JSON as payload I.e.:
   *  {
   *    "token": "d967a19940bdc4d498d0420a9fb12802ab5857a0a634ab73ae8984c5cf46ab3f9322dd5c1c3f069cc9d226ce47112747976c289cf6ae7b41a8ac72a7dc69c83f",
   *    "recommend_to": [
   *      {"id": "18", "recommend": "1"},
   *      {"id": "19", "recommend": "0"}
   *    ],
   *    "movie_tmdb_id": "550"
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
  
  if($loggedIn === false) {
    header('HTTP/1.1 401 Unauthorized');
    $response['message'] = 'Insufficient rights';
    $response['status'] = 401;
  } else if (isset($requestJson['recommend_to']) && isset($requestJson['movie_tmdb_id'])) {
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }
    
      foreach($requestJson["recommend_to"] as $recommend_to) {
        if(strcasecmp($recommend_to["recommend"], "1") == 0) {
          // Recommend movie insertion
          $stmt = $dbh->prepare("INSERT IGNORE INTO recommend_movies (recommend_by, recommend_to, tmdb_id, spoiler) 
                                  VALUES (:user_id, :recommend_to, :tmdb_id, :spoiler)");
          $stmt->bindParam(":tmdb_id", $requestJson["movie_tmdb_id"]);
          $stmt->bindParam(":recommend_to", $recommend_to["id"]);
          $stmt->bindParam(":spoiler", $requestJson["spoiler"]);
          $stmt->bindParam(":user_id", $userId);
          $stmt->execute();
          $recommendId = $dbh->lastInsertId();
          
          // user movies insertion
          $stmt = $dbh->prepare("INSERT INTO users_movies (user_id, movie_id, tmdb_id, imdb_id, recommend) 
                                  SELECT :recommend_to, 
                                      movie_id, 
                                      tmdb_id, 
                                      imdb_id, 
                                      (SELECT COUNT(id) FROM recommend_movies WHERE recommend_to=:recommend_to AND tmdb_id=:tmdb_id) 
                                    FROM movie_sources 
                                    WHERE tmdb_id=:tmdb_id
                                  ON DUPLICATE KEY UPDATE recommend=(SELECT COUNT(id) FROM recommend_movies WHERE recommend_to=:recommend_to AND tmdb_id=:tmdb_id)");
          $stmt->bindParam(":tmdb_id", $requestJson["movie_tmdb_id"]);
          $stmt->bindParam(":recommend_to", $recommend_to["id"]);
          $stmt->execute();
          
          $stmt = $dbh->prepare("SELECT (SELECT username FROM users WHERE id=:user_id) AS mailFrom, (SELECT username FROM users WHERE id=:recommend_to) AS mailTo, (SELECT m.title FROM movies AS m JOIN movie_sources AS ms ON ms.movie_id=m.id WHERE ms.tmdb_id=:tmdb_id LIMIT 1) AS title");
          $stmt->bindParam(":recommend_to", $recommend_to["id"]);
          $stmt->bindParam(":user_id", $userId);
          $stmt->bindParam(":tmdb_id", $requestJson["movie_tmdb_id"]);
          $stmt->execute();
          
          while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $mailTo = $row["mailTo"];
            $mailFrom = $row["mailFrom"];
            $title = $row["title"];
          }

          if(strlen($title) == 0) {
            $title = $requestJson["title"];
          }
          
          // When new recommendation we send an e-mail.
          if($recommendId != 0) {
            $hasSpoiler = (strlen($requestJson["spoiler"])>0) ? true : false;
            $movieSomMail->mailRecommendation($mailFrom, $mailTo, $requestJson["movie_tmdb_id"], $title, $hasSpoiler);            
          } else {
            // Check if a spoiler has been added or modified
            if(strlen($requestJson["spoiler"]) > 0) {
              $stmt = $dbh->prepare("SELECT id FROM recommend_movies WHERE 
                                      recommend_by=:user_id
                                      AND recommend_to=:recommend_to
                                      AND tmdb_id=:tmdb_id
                                      AND spoiler!=:spoiler");
              $stmt->bindParam(":spoiler", $requestJson["spoiler"]);
              $stmt->bindParam(":recommend_to", $recommend_to["id"]);
              $stmt->bindParam(":user_id", $userId);
              $stmt->bindParam(":tmdb_id", $requestJson["movie_tmdb_id"]);
              $stmt->execute();
              
              while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Send notice of added Spoiler.
                $movieSomMail->mailSpoilerAdded($mailFrom, $mailTo, $requestJson["movie_tmdb_id"], $title);  
                break;
              }
            }
            
            // We update the spoiler text.
            $stmt = $dbh->prepare("UPDATE recommend_movies SET spoiler=:spoiler WHERE 
                                    recommend_by=:user_id 
                                    AND recommend_to=:recommend_to 
                                    AND tmdb_id=:tmdb_id");
            $stmt->bindParam(":tmdb_id", $requestJson["movie_tmdb_id"]);
            $stmt->bindParam(":recommend_to", $recommend_to["id"]);
            $stmt->bindParam(":spoiler", $requestJson["spoiler"]);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
          }

        } else {
          $stmt = $dbh->prepare("DELETE FROM recommend_movies
                                  WHERE recommend_by=:user_id AND recommend_to=:recommend_to AND tmdb_id=:tmdb_id");
          $stmt->bindParam(":tmdb_id", $requestJson["movie_tmdb_id"]);
          $stmt->bindParam(":recommend_to", $recommend_to["id"]);
          $stmt->bindParam(":user_id", $userId);
          $stmt->execute();
        }
      }
      
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