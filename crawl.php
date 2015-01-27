<?php
  require_once('lib/config.php');

  try {
    $dbh = $db->connect();
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Update every day Movies not older than 1 month
    // Update every week Movies older than 1 month but not older than 3 months
    // Update every 2 weeks Movies older than 3 months but not older than 6 months
    // Update every month Movies older than 6 months
    $stmt = $dbh->prepare("SELECT ms.tmdb_id, title, release_date, updated FROM movie_sources AS ms
                            JOIN movie_ratings AS mr ON mr.source_id=ms.tmdb_id 
                            JOIN movies AS m ON m.id=ms.movie_id 
                          WHERE 
                            (m.release_date > NOW() - INTERVAL 1 MONTH 
                            AND mr.updated < NOW() - INTERVAL 1 DAY)
                            OR (m.release_date < NOW() - INTERVAL 1 MONTH 
                                AND m.release_date > NOW() - INTERVAL 3 MONTH 
                                AND mr.updated < NOW() - INTERVAL 1 WEEK)
                            OR (m.release_date < NOW() - INTERVAL 3 MONTH 
                                AND m.release_date > NOW() - INTERVAL 6 MONTH 
                                AND mr.updated < NOW() - INTERVAL 2 WEEK)
                            OR (m.release_date < NOW() - INTERVAL 6 MONTH
                                AND mr.updated < NOW() - INTERVAL 1 MONTH)");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      echo "cd C:/wamp/www/phantomjs-1.9.8-windows & moviesom.bat " . $row["tmdb_id"] . PHP_EOL;
      echo exec("cd C:/wamp/www/phantomjs-1.9.8-windows & moviesom.bat " . $row["tmdb_id"]) . PHP_EOL;
    }
  }
  catch(PDOException $e) {  
    echo $e . PHP_EOL;
  }

  echo $execTime->getTime() . PHP_EOL;