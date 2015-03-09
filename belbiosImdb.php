<?php
  require_once('lib/config.php');

  function getMovie($title, $stmt) {
    $searchString = "%" . $title . "%";
    $stmt->bindParam(":title", $searchString);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      return "{$row["title"]} {$row["tmdb_id"]} {$row["imdb_id"]}" . PHP_EOL;
    }
    return null;
  }
  
  function findMovie($title, $stmt) {
    $movie = getMovie($title, $stmt);
    if($movie == null) {
      $title = str_ireplace([", 2d", ", 3d", " 2d", " 3d", " IMAX"], "", $title);
      $movie = getMovie($title, $stmt);
    }
    if($movie == null) {
      $title = str_ireplace([", nl", ", ov", " nl", " ov"], "", $title);
      $movie = getMovie($title, $stmt);
    }
    if($movie == null) {
      $title = str_ireplace([", the", ", de"], "", $title);
      $movie = getMovie($title, $stmt);
    }
    if($movie == null) {
      $title = str_ireplace([" (12 jaar)"], "", $title);
      $movie = getMovie($title, $stmt);
    }
    
    if($movie == null) {
      echo $title . PHP_EOL;
    }

    return $movie;
  }
  
  try {
    $dbh = $db->connect();
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if ($dbh->inTransaction() === false) {
      $dbh->beginTransaction();
    }
    
    // Populate cities into the DB
    $stmt = $dbh->prepare("SELECT id, movie_name FROM cinema_dates_nl GROUP BY movie_belbios_id");
    $stmt2 = $dbh->prepare("SELECT * FROM movies AS m JOIN movie_sources AS ms ON ms.movie_id=m.id 
                            WHERE m.title LIKE :title
                              OR m.original_title LIKE :title
                              OR m.id=(SELECT movie_id FROM movie_alternative_titles WHERE title LIKE :title LIMIT 1)");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      findMovie($row["movie_name"], $stmt2);
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
