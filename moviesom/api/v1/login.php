<?php
  /**
   * Login.
   * Expects JSON as payload I.e.:
   *  {
   *    "username": "john@doe.nl",
   *    "password": "thesecret"
   *  }
   */



  header('HTTP/1.1 500 Internal Server Error');
  header('Content-type: application/json');
  $response = [];
  $response['status'] = 500;

  $requestJson = json_decode(file_get_contents("php://input"), true);
  // Credentials should be valid.
  $credentials->checkCredentials($requestJson['username'], $requestJson['password']);
  
  // Check if sufficient privileges.
  if($credentials->hasMoviesomAccess() === false) {
    header('HTTP/1.1 401 Unauthorized');
    $response['message'] = 'Insufficient rights';
    $response['status'] = 401;
  } else {
    header('HTTP/1.1 200 OK');
    $response['status'] = 200;
  }
  
  $response['execTime'] = $execTime->getTime();
  echo json_encode($response);
  
?>