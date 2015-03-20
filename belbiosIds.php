<?php
  require_once('lib/config.php');

  function getMovie($title, $stmt) {
    $result = null;
    $searchString = "%" . $title . "%";
    $stmt->bindParam(":title", $searchString);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $result = [];
      $result["movie_moviesom_id"] = $row["id"];
      $result["runtime"] = $row["runtime"];
    }
    return $result;
  }
  
  function findMovie($title, $stmt) {
    $movieSom = getMovie($title, $stmt);
    if($movieSom == null) {
      $title = str_ireplace([", 2d", ", 3d", " 2d", " 3d", " IMAX"], "", $title);
      $movieSom = getMovie($title, $stmt);
    }
    if($movieSom == null) {
      $title = str_ireplace([", nl", ", ov", " nl", " ov"], "", $title);
      $movieSom = getMovie($title, $stmt);
    }
    if($movieSom == null) {
      $title = str_ireplace([", the", ", de", ", a", ", het", ", la", ", il", ", les",  ", le", ", l'", ", une", ", een"], "", $title);
      $movieSom = getMovie($title, $stmt);
    }
    if($movieSom == null) {
      $title = str_ireplace([" (12 jaar)"], "", $title);
      $movieSom = getMovie($title, $stmt);
    }

    return $movieSom;
  }
  
  try {
    $dbh = $db->connect();
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if ($dbh->inTransaction() === false) {
      $dbh->beginTransaction();
    }
    
    // Populate cities into the DB
    $stmt = $dbh->prepare("SELECT movie_belbios_id, movie_name, timestamp FROM cinema_dates_nl GROUP BY movie_belbios_id");
    $stmt2 = $dbh->prepare("SELECT m.id, m.title, ms.tmdb_id, ms.imdb_id, m.runtime FROM movies AS m JOIN movie_sources AS ms ON ms.movie_id=m.id 
                            WHERE m.title LIKE :title
                              OR m.original_title LIKE :title
                              OR m.id=(SELECT movie_id FROM movie_alternative_titles WHERE title LIKE :title LIMIT 1)");
    $stmt3 = $dbh->prepare("UPDATE cinema_dates_nl SET movie_moviesom_id=:movie_moviesom_id, timestamp_end=(timestamp + :timestamp_end) WHERE movie_belbios_id=:movie_belbios_id");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $movieSom = findMovie($row["movie_name"], $stmt2);
      
      if($movieSom != null) {
        $stmt3->bindParam(":movie_moviesom_id", $movieSom["movie_moviesom_id"]);
        $timestampEnd  = $movieSom["runtime"] * 60;
        $stmt3->bindParam(":timestamp_end", $timestampEnd);
        $stmt3->bindParam(":movie_belbios_id", $row["movie_belbios_id"]);
        $stmt3->execute();
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
