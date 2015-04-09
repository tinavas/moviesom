<?php
  /**
   * Get cities movies.
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
      $stmt = $dbh->prepare("SELECT movie_belbios_id, movie_name, runtime FROM willim_moviesom.cinema_dates_nl AS cd
                            LEFT JOIN movies AS m ON m.id=cd.movie_moviesom_id
                            WHERE city_id=:city_id
                            AND timestamp>=:start AND timestamp<:end
                            GROUP BY movie_belbios_id
                            ORDER BY movie_name ASC");
      $stmt->bindParam(":city_id", $requestJson["city_id"]);
      $stmt->bindParam(":start", $start);
      $stmt->bindParam(":end", $end);
      $stmt->execute();
      $movies = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $movies[] = $row;
      }
      $response["message"] = $movies;
      
      
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