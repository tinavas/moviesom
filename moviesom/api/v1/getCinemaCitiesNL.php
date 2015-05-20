<?php
  /**
   * Get cinema cities.
   */



  header('HTTP/1.1 500 Internal Server Error');
  header('Content-type: application/json');
  $response = [];
  $response['status'] = 500;
  
  $requestJson = json_decode(file_get_contents("php://input"), true);
  
  // We need at least one of the following arrays in order to proceed with searching.
  try {
    $dbh = $db->connect();
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $dbh->prepare("SELECT * FROM cinema_cities_nl ORDER BY name ASC");
    $stmt->execute();
    $cities = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $cities[] = $row;
    }
    $response["message"] = $cities;
    
    
    header('HTTP/1.1 200 OK');
    $response['status'] = 200;
  }
  catch(PDOException $e) {  
    $response['message'] = $e;
  }
  
?>