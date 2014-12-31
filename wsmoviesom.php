<?php
  require_once('lib/config.php');
  require_once('lib/password_compat/password.php');
  
  $version = "v1";
  if(isset($_REQUEST['v'])) {
    switch($_REQUEST['v']) {
      case "v2":
        $version = "v2";
      break;
      case "v1":
        $version = "v1";
        break;
      default:
        // This will always be updated to the latest version.
        $version = "v1";
    }
  }

  $files = scandir(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'moviesom' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . $version);
  $services = explode(",", $_REQUEST['service']);
  array_walk($services, function(&$value, $key) { $value .= '.php'; });
  $services = array_intersect($services, $files);
  $multiResponse = [];
  if(sizeof($services) > 0) {
    foreach($services as $service) {
      require(dirname(__FILE__)  . DIRECTORY_SEPARATOR . 'moviesom' . DIRECTORY_SEPARATOR . "api" . DIRECTORY_SEPARATOR . $version . DIRECTORY_SEPARATOR . $service);
      $multiResponse[str_ireplace(".php", "", $service)] = $response;
    }
    $multiResponse['execTime'] = $execTime->getTime();

    echo json_encode($multiResponse);
  } else {
    echo "Unknown webservice\r\n";
  }
