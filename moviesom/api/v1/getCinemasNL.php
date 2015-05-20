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
      $stmt = $dbh->prepare("SELECT id, name FROM cinemas_nl WHERE city_id=:city_id ORDER BY name ASC");
      $stmt->bindParam(":city_id", $requestJson["city_id"]);
      $stmt->execute();
      $cinemas = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cinemas[] = $row;
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