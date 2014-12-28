<?php
  /**
   * Get global app configuration.
   */



  header('HTTP/1.1 500 Internal Server Error');
  header('Content-type: application/json');
  $response = [];
  $response['status'] = 500;
  
  $requestJson = json_decode(file_get_contents("php://input"), true);
  
  $response["message"]["html2xpath"]["u"] = "http://m.imdb.com/title/";
  $response["message"]["html2xpath"]["x"][] = "//div[contains(@id,'ratings-bar')]/div/span[2]";
  $response["message"]["html2xpath"]["x"][] = "//div[contains(@id,'ratings-bar')]/div/span[2]/small/text()[2]";
    
  header('HTTP/1.1 200 OK');
  $response['status'] = 200;
  
  $response['execTime'] = $execTime->getTime();
  echo json_encode($response);
  
?>