<?php
  require_once('lib/config.php');

  function queryBelbios($url) {
    $curl = curl_init($url);
    curl_setopt_array($curl, array(
      CURLOPT_RETURNTRANSFER => 1
    ));
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
  }
  
  try {
    $dbh = $db->connect();
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if ($dbh->inTransaction() === false) {
      $dbh->beginTransaction();
    }

    // Retrieve Belbios cities.
    $jsonCities = json_decode(queryBelbios('http://www.belbios.nl/ajax/cities/'), true);
    echo $execTime->getTime() . PHP_EOL;
    echo "Cities retrieved" . PHP_EOL;
    
    // We clear all the tables before population
    $stmt = $dbh->prepare("TRUNCATE TABLE cinemas_nl");
    $stmt->execute();
    $stmt = $dbh->prepare("TRUNCATE TABLE cinema_cities_nl");
    $stmt->execute();
    $stmt = $dbh->prepare("TRUNCATE TABLE cinema_movies_nl");
    $stmt->execute();
    $stmt = $dbh->prepare("TRUNCATE TABLE cinema_cities_movies_nl");
    $stmt->execute();
    $stmt = $dbh->prepare("TRUNCATE TABLE cinema_dates_nl");
    $stmt->execute();
    echo $execTime->getTime() . PHP_EOL;
    echo "Tables truncated" . PHP_EOL;
    
    // Populate cities into the DB
    $stmt = $dbh->prepare("INSERT IGNORE INTO cinema_cities_nl (belbios_id, name) VALUES (:belbios_id, :name)");
    $stmt2 = $dbh->prepare("INSERT IGNORE INTO cinemas_nl (belbios_id, name, city_id, city_name) VALUES (:belbios_id, :name, :city_id, :city_name)");
    $stmt3 = $dbh->prepare("INSERT IGNORE INTO cinema_movies_nl (belbios_id, title) VALUES (:belbios_id, :title)");
    $stmt4 = $dbh->prepare("INSERT IGNORE INTO cinema_cities_movies_nl (city_id, city_name, movie_id, movie_name) VALUES (:city_id, :city_name, (SELECT id FROM cinema_movies_nl WHERE belbios_id=:movie_belbios_id), :movie_name)");
    $stmt5 = $dbh->prepare("INSERT IGNORE INTO cinema_dates_nl (city_id, city_name, cinema_id, cinema_name, movie_belbios_id, movie_name, movie_date, movie_time, timestamp) VALUES (:city_id, :city_name, :cinema_id, :cinema_name, :movie_belbios_id, :movie_name, :movie_date, :movie_time, :timestamp)");
    foreach($jsonCities as $key=>$value) {
      $stmt->bindParam(":belbios_id", $value["belbios_id"]);
      $stmt->bindParam(":name", $value["name"]);
      $stmt->execute();
      $city_id = $dbh->lastInsertId();
      echo $execTime->getTime() . PHP_EOL;
      echo "Cities {$value["name"]} inserted" . PHP_EOL;
      
      // Populate the cinemas per city.
      $jsonCityCinemas = json_decode(queryBelbios("http://www.belbios.nl/ajax/cinemas/{$value["belbios_id"]}/0"), true);
      foreach($jsonCityCinemas as $key2=>$value2) {
        $stmt2->bindParam(":belbios_id", $value2["belbios_id"]);
        $stmt2->bindParam(":name", $value2["name"]);
        $stmt2->bindParam(":city_id", $city_id);
        $stmt2->bindParam(":city_name", $value["name"]); // This is the city name. Note the $value.
        $stmt2->execute();
        $cinema_id = $dbh->lastInsertId();
        echo $execTime->getTime() . PHP_EOL;
        echo "Cinema {$value2["name"]} inserted" . PHP_EOL;
        
        // Populate the movies per cinema
        $jsonMovies = json_decode(queryBelbios("http://www.belbios.nl/ajax/movies/{$value2["belbios_id"]}"), true);
        foreach($jsonMovies as $key3=>$value3) {
          $stmt3->bindParam(":belbios_id", $value3["belbios_id"]);
          $stmt3->bindParam(":title", $value3["title"]);
          $stmt3->execute();
          echo $execTime->getTime() . PHP_EOL;
          echo "Movies {$value3["title"]} inserted" . PHP_EOL;
          
          // Populate the movies per city
          $stmt4->bindParam(":city_id", $city_id);
          $stmt4->bindParam(":city_name", $value["name"]);
          $stmt4->bindParam(":movie_belbios_id", $value3["belbios_id"]);
          $stmt4->bindParam(":movie_name", $value3["title"]);
          $stmt4->execute();
          echo $execTime->getTime() . PHP_EOL;
          echo "City {$value["name"]} -> Movie {$value3["title"]} inserted" . PHP_EOL;
          
          $jsonDates = json_decode(queryBelbios("http://www.belbios.nl/ajax/dates/{$value2["belbios_id"]}/{$value3["belbios_id"]}"), true);
          echo "http://www.belbios.nl/ajax/dates/{$value2["belbios_id"]}/{$value3["belbios_id"]}";
          foreach($jsonDates as $key4=>$value4) {
            // Populate the movie dates per cinema
            $stmt5->bindParam(":city_id", $city_id);
            $stmt5->bindParam(":city_name", $value["name"]);
            $stmt5->bindParam(":cinema_id", $cinema_id);
            $stmt5->bindParam(":cinema_name", $value2["name"]);
            $stmt5->bindParam(":movie_belbios_id", $value3["belbios_id"]);
            $stmt5->bindParam(":movie_name", $value3["title"]);
            $stmt5->bindParam(":movie_date", $value4["date"]);
            $stmt5->bindParam(":movie_time", $value4["time"]);
            $stmt5->bindParam(":timestamp", $value4["timestamp"]);
            $stmt5->execute();
            echo $execTime->getTime() . PHP_EOL;
            echo "Date {$value4["date"]} Time {$value4["time"]} Timestamp {$value4["timestamp"]} inserted" . PHP_EOL;
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
