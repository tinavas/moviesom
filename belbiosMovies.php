<?php
  require_once('lib/config.php');
  
  $tmdbApiUrl = "https://api.themoviedb.org/3/";

  function searchTmdb($query) {
    global $tmdbApiUrl; 
    $query = urlencode($query);
    $curl = curl_init("{$tmdbApiUrl}search/movie?api_key=9b204c4da976e672c9a5f4ea7edd680e&query={$query}&page=1");
    curl_setopt_array($curl, array(
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_RETURNTRANSFER => 1
    ));
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
  }
  
  function tmdbMovie($id) {
    global $tmdbApiUrl; 
    $curl = curl_init("{$tmdbApiUrl}movie/{$id}?api_key=9b204c4da976e672c9a5f4ea7edd680e&append_to_response=alternative_titles");
    curl_setopt_array($curl, array(
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_RETURNTRANSFER => 1
    ));
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
  }
  
  function stripFormat($title) {
    return str_ireplace([", 2d", ", 3d", " 2d", " 3d", " IMAX", "...", ".."], "", $title);
  }
  
  function stripLanguage($title) {
    return str_ireplace([", nl", ", ov", " nl", " ov", " Deutsch"], "", $title);
  }
  
  function stripArticle($title) {
    return str_ireplace([", the", ", de", ", a", ", het", ", la", ", il", ", les",  ", le", ", l'", ", une", ", een"], "", $title);
  }
  
  function stripAge($title) {
    return str_ireplace([" (12 jaar)", " (16 jaar)"], "", $title);
  }
  
  try {
    $dbh = $db->connect();
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if ($dbh->inTransaction() === false) {
      $dbh->beginTransaction();
    }
    
    /**
     * We download the missing movies from TMDb
     */
    $stmt4 = $dbh->prepare("SELECT movie_name FROM cinema_dates_nl where movie_moviesom_id IS NULL GROUP BY movie_name");
    $stmt4->execute();
    while ($row2 = $stmt4->fetch(PDO::FETCH_ASSOC)) {
      $tmdbResult = searchTmdb(stripAge(stripArticle(stripLanguage(stripFormat($row2["movie_name"])))));
      $resultsArray = json_decode($tmdbResult, true);
      if(isset($resultsArray["results"])) {
        foreach($resultsArray["results"] as $movie) {
          $result = json_decode(tmdbMovie($movie["id"]), true);
          $stmt8 = $dbh->prepare("SELECT id FROM movie_ratings WHERE source_id=:tmdb_id");
          $stmt8->bindParam(":tmdb_id", $result["id"]);
          $stmt8->execute();
          $movieExists = false;
          while ($row3 = $stmt8->fetch(PDO::FETCH_ASSOC)) {
            $movieExists = true;
            break;
          }
          if(isset($result) && $movieExists === false) {
          var_dump($result["title"]);
          var_dump($result["id"]);
            $stmt5 = $dbh->prepare("INSERT IGNORE INTO movies 
                                    (title, original_title, runtime, release_date, backdrop_path, poster_path) 
                                      VALUES
                                    (:title, :original_title, :runtime, :release_date, :backdrop_path, :poster_path)");
            $stmt5->bindParam(":title", $result["title"]);
            $stmt5->bindParam(":original_title", $result["original_title"]);
            $stmt5->bindParam(":runtime", $result["runtime"]);
            if(isset($result["release_date"]) && strlen($result["release_date"]) > 0) {
              $stmt5->bindParam(":release_date", $result["release_date"]);
            } else {
              $stmt5->bindValue(":release_date", null);
            }
            $stmt5->bindParam(":backdrop_path", $result["backdrop_path"]);
            $stmt5->bindParam(":poster_path", $result["poster_path"]);
            $stmt5->execute();
            
            $movieSomId = $dbh->lastInsertId();
            $stmt6 = $dbh->prepare("INSERT IGNORE INTO movie_ratings
                                    (movie_id, source_id, rating, votes) 
                                      VALUES
                                    (:movie_id, :source_id, :rating, :votes)");
            $stmt6->bindParam(":movie_id", $movieSomId);
            $stmt6->bindParam(":source_id", $result["id"]);
            $stmt6->bindParam(":rating", $result["vote_average"]);
            $stmt6->bindParam(":votes", $result["vote_count"]);
            $stmt6->execute();
            
            if(strlen($result["imdb_id"]) > 0) {
              var_dump("{$movieSomId} {$result["id"]} {$result["imdb_id"]}");
              $stmt7 = $dbh->prepare("INSERT IGNORE INTO movie_sources
                                      (movie_id, tmdb_id, imdb_id) 
                                        VALUES
                                      (:movie_id, :tmdb_id, :imdb_id)");
              $stmt7->bindParam(":movie_id", $movieSomId);
              $stmt7->bindParam(":tmdb_id", $result["id"]);
              $stmt7->bindParam(":imdb_id", $result["imdb_id"]);
              $stmt7->execute();
            }
            
            foreach($result["alternative_titles"]["titles"] as $altTitle) {
              $stmt9 = $dbh->prepare("INSERT IGNORE INTO movie_alternative_titles
                                      (iso_3166_1, movie_id, title) 
                                        VALUES
                                      (:iso_3166_1, :movie_id, :title)");
              $stmt9->bindParam(":iso_3166_1", $altTitle["iso_3166_1"]);
              $stmt9->bindParam(":movie_id", $movieSomId);
              $stmt9->bindParam(":title", $altTitle["title"]);
              $stmt9->execute();
            }
          }
        }
      }
    }
    
    if($dbh->commit()) {
      echo $execTime->getTime() . PHP_EOL;
      echo "Transaction OK" . PHP_EOL;
    } else {
      echo $execTime->getTime() . PHP_EOL;
      echo "Transaction ERROR" . PHP_EOL;
    }
  }
  catch(PDOException $e) {  
    echo $execTime->getTime() . PHP_EOL;
    echo $e . PHP_EOL;
  }

  echo $execTime->getTime() . PHP_EOL;
  echo "Done" . PHP_EOL;
