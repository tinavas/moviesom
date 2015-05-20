<?php
  /**
   * Get cinema movie time tables.
   * Expects JSON as payload I.e.:
   *  {
   *    "cinema_id": 1
   *  }
   */



  header('HTTP/1.1 500 Internal Server Error');
  header('Content-type: application/json');
  $response = [];
  $response['status'] = 500;
  
  $requestJson = json_decode(file_get_contents("php://input"), true);
  
  if(isset($requestJson["city_id"]) && isset($requestJson["movie_id"])) {
    // We need at least one of the following arrays in order to proceed with searching.
    try {
      $start = strtotime('today');
      $end = strtotime('tomorrow');
      if(isset($requestJson["timestamp"])) {
        $start = strtotime('today', $requestJson["timestamp"]);
        $end = strtotime('tomorrow', $requestJson["timestamp"]);
      }

      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $stmt = $dbh->prepare("SELECT movie_name, tmdb_id, cinema_id, cinema_name
                              FROM cinema_dates_nl AS cd
                              LEFT JOIN movies AS m ON m.id=cd.movie_moviesom_id
                              LEFT JOIN movie_sources AS ms ON ms.movie_id=m.id
                              WHERE timestamp>=:start AND timestamp<:end AND city_id=:city_id AND movie_belbios_id=:movie_belbios_id
                              GROUP BY cinema_id
                              ORDER BY movie_name ASC");
      $stmt2 = $dbh->prepare("SELECT movie_time, timestamp, timestamp_end
                              FROM cinema_dates_nl AS cd
                              LEFT JOIN movies AS m ON m.id=cd.movie_moviesom_id
                              LEFT JOIN movie_sources AS ms ON ms.movie_id=m.id
                              WHERE timestamp>=:start AND timestamp<:end AND city_id=:city_id AND movie_belbios_id=:movie_belbios_id AND cinema_id=:cinema_id
                              ORDER BY timestamp ASC");
      $stmt->bindParam(":city_id", $requestJson["city_id"]);
      $stmt->bindParam(":movie_belbios_id", $requestJson["movie_id"]);
      $stmt->bindParam(":start", $start);
      $stmt->bindParam(":end", $end);
      $stmt->execute();
      $cinemas = [];
      $cinemas["cinemas"] = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cinemaDetails = [];
        $cinemaDetails["cinema_name"] = $row["cinema_name"];
        $cinemaDetails["times"] = [];
        $stmt2->bindParam(":city_id", $requestJson["city_id"]);
        $stmt2->bindParam(":movie_belbios_id", $requestJson["movie_id"]);
        $stmt2->bindParam(":cinema_id", $row["cinema_id"]);
        $stmt2->bindParam(":start", $start);
        $stmt2->bindParam(":end", $end);
        $stmt2->execute();
        while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
          $times = [];
          $times["timestamp"] = $row2["timestamp"];
          $times["timestamp_end"] = $row2["timestamp_end"];
          $times["movie_time"] = $row2["movie_time"];
          array_push($cinemaDetails["times"], $times);
        }
        array_push($cinemas["cinemas"], $cinemaDetails);
      }
      $response["message"] = $cinemas;
      
      
      header('HTTP/1.1 200 OK');
      $response['status'] = 200;
    }
    catch(PDOException $e) {  
      $response['message'] = $e;
    }
  }
  
?>