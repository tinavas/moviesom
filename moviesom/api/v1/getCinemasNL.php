<?php
  /**
   * Get cities cinemas.
   * Expects JSON as payload I.e.:
   *  {
   *    "city_id": 1
   *  }
   */



  header('HTTP/1.1 500 Internal Server Error');
  header('Content-type: application/json');
  $response = [];
  $response['status'] = 500;
  
  $requestJson = json_decode(file_get_contents("php://input"), true);
  
  if(isset($requestJson["city_id"])) {
    // We need at least one of the following arrays in order to proceed with searching.
    try {
      $start = strtotime('today');
      $end = strtotime('tomorrow');
      
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }
      $stmt = $dbh->prepare("SELECT id, name FROM cinemas_nl WHERE city_id=:city_id ORDER BY name ASC");
      $stmt2 = $dbh->prepare("SELECT movie_belbios_id, movie_name FROM cinema_dates_nl WHERE timestamp>=:start AND timestamp<:end AND cinema_id=:cinema_id GROUP BY movie_belbios_id ORDER BY movie_name ASC");
      $stmt3 = $dbh->prepare("SELECT movie_time, timestamp FROM cinema_dates_nl WHERE timestamp>=:start AND timestamp<:end AND cinema_id=:cinema_id AND movie_belbios_id=:movie_belbios_id ORDER BY movie_name ASC");
      $stmt->bindParam(":city_id", $requestJson["city_id"]);
      $stmt->execute();
      $cinemas = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stmt2->bindParam(":cinema_id", $row["id"]);
        $stmt2->bindParam(":start", $start);
        $stmt2->bindParam(":end", $end);
        $stmt2->execute();
        $row["movies"] = [];
        while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
          $stmt3->bindParam(":cinema_id", $row["id"]);
          $stmt3->bindParam(":movie_belbios_id", $row2["movie_belbios_id"]);
          $stmt3->bindParam(":start", $start);
          $stmt3->bindParam(":end", $end);
          $stmt3->execute();
          $row2["times"] = [];
          while ($row3 = $stmt3->fetch(PDO::FETCH_ASSOC)) {
            array_push($row2["times"], $row3);
          }
          array_push($row["movies"], $row2);
        }
        $cinemas[] = $row;
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