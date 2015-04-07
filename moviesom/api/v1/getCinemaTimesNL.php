<?php
  /**
   * Get cinema time tables.
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
  
  if(isset($requestJson["cinema_id"])) {
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
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }
      $stmt2 = $dbh->prepare("SELECT movie_belbios_id, movie_name FROM cinema_dates_nl 
                              WHERE timestamp>=:start AND timestamp<:end AND cinema_id=:cinema_id 
                              GROUP BY movie_belbios_id ORDER BY movie_name ASC");
      $stmt3 = $dbh->prepare("SELECT movie_time, timestamp, timestamp_end, IF(m.runtime IS NULL, 0, m.runtime) AS runtime, tmdb_id FROM cinema_dates_nl AS cd
                              LEFT JOIN movies AS m ON m.id=cd.movie_moviesom_id
                              LEFT JOIN movie_sources AS ms ON ms.movie_id=m.id
                              WHERE timestamp>=:start AND timestamp<:end AND cinema_id=:cinema_id AND movie_belbios_id=:movie_belbios_id 
                              ORDER BY movie_name ASC");
      $stmt2->bindParam(":cinema_id", $requestJson["cinema_id"]);
      $stmt2->bindParam(":start", $start);
      $stmt2->bindParam(":end", $end);
      $stmt2->execute();
      $cinemas = [];
      $cinemas["movies"] = [];
      while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        $stmt3->bindParam(":cinema_id", $requestJson["cinema_id"]);
        $stmt3->bindParam(":movie_belbios_id", $row2["movie_belbios_id"]);
        $stmt3->bindParam(":start", $start);
        $stmt3->bindParam(":end", $end);
        $stmt3->execute();
        $row2["times"] = [];
        $row2["movie_id"] = "";
        while ($row3 = $stmt3->fetch(PDO::FETCH_ASSOC)) {
          $row2["tmdb_id"] = $row3["tmdb_id"];
          array_push($row2["times"], $row3);
        }
        array_push($cinemas["movies"], $row2);
      }
      $response["message"] = $cinemas;
      
      
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