<?php
  require_once('lib/config.php');

  try {
    $dbh = $db->connect();
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Update every day Movies not older than 1 month
    // Update every week Movies older than 1 month but not older than 3 months
    // Update every 2 weeks Movies older than 3 months but not older than 6 months
    // Update every month Movies older than 6 months
    $stmt = $dbh->prepare("SELECT ms.tmdb_id FROM movie_sources AS ms
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
      echo "cd ../phantomjs-1.9.8-windows & moviesom.bat tmdbMovieId " . $row["tmdb_id"] . PHP_EOL;
      echo exec("cd ../phantomjs-1.9.8-windows & moviesom.bat tmdbMovieId " . $row["tmdb_id"]) . PHP_EOL;
    }
    
    // Update every day TV Series not older than 1 month
    // Update every week TV Series older than 1 month but not older than 3 months
    // Update every 2 weeks TV Series older than 3 months but not older than 6 months
    // Update every month TV Series older than 6 months
    $stmt = $dbh->prepare("SELECT ts.tmdb_id FROM tv_sources AS ts
                            JOIN tv_ratings AS tr ON tr.source_id=ts.tmdb_id 
                            JOIN tv AS tv ON tv.id=ts.tv_id 
                          WHERE 
                            (tv.last_air_date > NOW() - INTERVAL 1 MONTH 
                            AND tr.updated < NOW() - INTERVAL 1 DAY)
                            OR (tv.last_air_date < NOW() - INTERVAL 1 MONTH 
                                AND tv.last_air_date > NOW() - INTERVAL 3 MONTH 
                                AND tr.updated < NOW() - INTERVAL 1 WEEK)
                            OR (tv.last_air_date < NOW() - INTERVAL 3 MONTH 
                                AND tv.last_air_date > NOW() - INTERVAL 6 MONTH 
                                AND tr.updated < NOW() - INTERVAL 2 WEEK)
                            OR (tv.last_air_date < NOW() - INTERVAL 6 MONTH
                                AND tr.updated < NOW() - INTERVAL 1 MONTH)");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      echo "cd ../phantomjs-1.9.8-windows & moviesom.bat tmdbTvId " . $row["tmdb_id"] . PHP_EOL;
      echo exec("cd ../phantomjs-1.9.8-windows & moviesom.bat tmdbTvId " . $row["tmdb_id"]) . PHP_EOL;
    }
    
    // Update every day TV Episode not older than 1 month
    // Update every week TV Episode older than 1 month but not older than 3 months
    // Update every 2 weeks TV Episode older than 3 months but not older than 6 months
    // Update every month TV Episode older than 6 months
    $stmt = $dbh->prepare("SELECT te.tmdb_tv_id, season_number, episode_number FROM tv_episode_sources AS tes
                            JOIN tv_episode_ratings AS ter ON ter.source_id=tes.tmdb_id 
                            JOIN tv_episodes AS te ON te.id=tes.tv_episode_id 
                          WHERE 
                            (te.air_date > NOW() - INTERVAL 1 MONTH 
                            AND ter.updated < NOW() - INTERVAL 1 DAY)
                            OR (te.air_date < NOW() - INTERVAL 1 MONTH 
                                AND te.air_date > NOW() - INTERVAL 3 MONTH 
                                AND ter.updated < NOW() - INTERVAL 1 WEEK)
                            OR (te.air_date < NOW() - INTERVAL 3 MONTH 
                                AND te.air_date > NOW() - INTERVAL 6 MONTH 
                                AND ter.updated < NOW() - INTERVAL 2 WEEK)
                            OR (te.air_date < NOW() - INTERVAL 6 MONTH
                                AND ter.updated < NOW() - INTERVAL 1 MONTH)");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      echo "cd ../phantomjs-1.9.8-windows & moviesom.bat tmdbTvId " . $row["tmdb_tv_id"] . " " . $row["season_number"] . " " . $row["episode_number"] . PHP_EOL;
      echo exec("cd ../phantomjs-1.9.8-windows & moviesom.bat tmdbTvId " . $row["tmdb_tv_id"] . " " . $row["season_number"] . " " . $row["episode_number"]) . PHP_EOL;
    }
    
  }
  catch(PDOException $e) {  
    echo $e . PHP_EOL;
  }

  echo $execTime->getTime() . PHP_EOL;