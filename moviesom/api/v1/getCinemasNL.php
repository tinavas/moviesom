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
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }
      $stmt = $dbh->prepare("SELECT * FROM cinemas_nl WHERE city_id=:city_id ORDER BY name ASC");
      $stmt2 = $dbh->prepare("SELECT * FROM cinema_dates_nl WHERE cinema_id=:cinema_id ORDER BY movie_name ASC");
      $stmt->bindParam(":city_id", $requestJson["city_id"]);
      $stmt->execute();
      $cinemas = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stmt2->bindParam(":cinema_id", $row["id"]);
        $stmt2->execute();
        while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
          $row["movies"] = $row2;
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