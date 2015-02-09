<?php
  /**
   * Add user connection.
   * Expects JSON as payload I.e.:
   *  {
   *    "token": "d967a19940bdc4d498d0420a9fb12802ab5857a0a634ab73ae8984c5cf46ab3f9322dd5c1c3f069cc9d226ce47112747976c289cf6ae7b41a8ac72a7dc69c83f",
   *    "id": "18",
   *    "consent": "1"
   *  }
   */

  header('HTTP/1.1 500 Internal Server Error');
  header('Content-type: application/json');
  $response = [];
  $response['status'] = 500;
  
  $requestJson = json_decode(file_get_contents("php://input"), true);

  // Login token should be valid.
  if(isset($requestJson['token']) && strlen($requestJson['token']) > 0) {
    $credentials->checkLoginToken($requestJson['token']);
  }
  
  $loggedIn = $credentials->hasMoviesomAccess();
  $userId = $credentials->getUserId();  
  $username = $credentials->getUsername();
  
  if($loggedIn === false) {
    header('HTTP/1.1 401 Unauthorized');
    $response['message'] = 'Insufficient rights';
    $response['status'] = 401;
  } else if (isset($requestJson['id']) && isset($requestJson['consent'])) {
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }
      
      $connection = [];
      
      $stmt = $dbh->prepare("SELECT :user_id AS self_id, uc.*, u.id AS uid1, u2.id AS uid2, 
                                u.username AS user1, u2.username AS user2
                              FROM users_connections AS uc
                                JOIN users AS u ON u.id=uc.user_id 
                                JOIN users AS u2 ON u2.id=uc.user_id2 
                              WHERE (user_id=:user_id AND user_id2=:id) 
                                OR (user_id2=:user_id AND user_id=:id)");
      $stmt->bindParam(":id", $requestJson["id"]);
      $stmt->bindParam(":user_id", $userId);
      $stmt->execute();
      $connectionExists = false;
      $consent = 0;
      $consent2 = 0;
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $row["id"];
        if(intval($row["uid1"]) == $userId) {
          $consent = $requestJson['consent'];
          $row["consent"] = $consent;
          $consent2 = $row["consent2"];
          $fromMail = $row["user1"];
          $toMail = $row["user2"];
        }
        if(intval($row["uid2"]) == $userId) {
          $consent2 = $requestJson['consent'];
          $row["consent2"] = $consent2;
          $consent = $row["consent"];
          $fromMail = $row["user2"];
          $toMail = $row["user1"];
        }
        $connection[] = $row;
        $connectionExists = true;
        break;
      }

      $stmt = $dbh->prepare("UPDATE users_connections SET consent=:consent, consent2=:consent2 WHERE id=:id");
      $stmt->bindParam(":id", $id);
      $stmt->bindParam(":consent", $consent);
      $stmt->bindParam(":consent2", $consent2);
      $stmt->execute();

    
      // To send HTML mail, the Content-type header must be set
      $headers  = 'MIME-Version: 1.0' . "\r\n";
      $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

      // Additional headers
      $headers .= 'From: MovieSom <webmaster@moviesom.com>' . "\r\n";

      $protocol = explode("/", $_SERVER['SERVER_PROTOCOL']);
      $protocol = strtolower(array_shift($protocol));
      if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
        $protocol = "https";
      }
      $heredocMail = <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<!-- If you delete this meta tag, Half Life 3 will never be released. -->
<meta name="viewport" content="width=device-width" />

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>MovieSom</title>
<style>
*{margin:0;padding:0}*{font-family:"Helvetica Neue","Helvetica",Helvetica,Arial,sans-serif}img{max-width:100%}.collapse{margin:0;padding:0}body{-webkit-font-smoothing:antialiased;-webkit-text-size-adjust:none;width:100%!important;height:100%}a{color:#2ba6cb}.btn{text-decoration:none;color:#fff;background-color:#666;padding:10px 16px;font-weight:bold;margin-right:10px;text-align:center;cursor:pointer;display:inline-block}p.callout{padding:15px;background-color:#ecf8ff;margin-bottom:15px}.callout a{font-weight:bold;color:#2ba6cb}table.social{background-color:#ebebeb}.social .soc-btn{padding:3px 7px;font-size:12px;margin-bottom:10px;text-decoration:none;color:#fff;font-weight:bold;display:block;text-align:center}a.fb{background-color:#3b5998!important}a.tw{background-color:#1daced!important}a.gp{background-color:#db4a39!important}a.ms{background-color:#000!important}.sidebar .soc-btn{display:block;width:100%}table.head-wrap{width:100%}.header.container table td.logo{padding:15px}.header.container table td.label{padding:15px;padding-left:0}table.body-wrap{width:100%}table.footer-wrap{width:100%;clear:both!important}.footer-wrap .container td.content p{border-top:1px solid #d7d7d7;padding-top:15px}.footer-wrap .container td.content p{font-size:10px;font-weight:bold}h1,h2,h3,h4,h5,h6{font-family:"HelveticaNeue-Light","Helvetica Neue Light","Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif;line-height:1.1;margin-bottom:15px;color:#000}h1 small,h2 small,h3 small,h4 small,h5 small,h6 small{font-size:60%;color:#6f6f6f;line-height:0;text-transform:none}h1{font-weight:200;font-size:44px}h2{font-weight:200;font-size:37px}h3{font-weight:500;font-size:27px}h4{font-weight:500;font-size:23px}h5{font-weight:900;font-size:17px}h6{font-weight:900;font-size:14px;text-transform:uppercase;color:#444}.collapse{margin:0!important}p,ul{margin-bottom:10px;font-weight:normal;font-size:14px;line-height:1.6}p.lead{font-size:17px}p.last{margin-bottom:0}ul li{margin-left:5px;list-style-position:inside}ul.sidebar{background:#ebebeb;display:block;list-style-type:none}ul.sidebar li{display:block;margin:0}ul.sidebar li a{text-decoration:none;color:#666;padding:10px 16px;margin-right:10px;cursor:pointer;border-bottom:1px solid #777;border-top:1px solid #fff;display:block;margin:0}ul.sidebar li a.last{border-bottom-width:0}ul.sidebar li a h1,ul.sidebar li a h2,ul.sidebar li a h3,ul.sidebar li a h4,ul.sidebar li a h5,ul.sidebar li a h6,ul.sidebar li a p{margin-bottom:0!important}.container{display:block!important;max-width:600px!important;margin:0 auto!important;clear:both!important}.content{padding:15px;max-width:600px;margin:0 auto;display:block}.content table{width:100%}.column{width:300px;float:left}.column tr td{padding:15px}.column-wrap{padding:0!important;margin:0 auto;max-width:600px!important}.column table{width:100%}.social .column{width:280px;min-width:279px;float:left}.clear{display:block;clear:both}@media only screen and (max-width:600px){a[class="btn"]{display:block!important;margin-bottom:10px!important;background-image:none!important;margin-right:0!important}div[class="column"]{width:auto!important;float:none!important}table.social div[class="column"]{width:auto!important}}
</style>
<link rel="stylesheet" type="text/css" href="{$protocol}://{$_SERVER["SERVER_NAME"]}/css/email.css" />

</head>
 
<body bgcolor="#FFFFFF">

<!-- HEADER -->
<table class="head-wrap" bgcolor="#333">
  <tr>
    <td></td>
    <td class="header container" >
      <div class="content">
        <table bgcolor="#333">
          <tr>
            <td><h6 class="collapse" style="color: white;">MovieSom</h1></h6>
            <td align="right"><h6 class="collapse" style="color: #999">Connect</h6></td>
          </tr>
        </table>
      </div>
    </td>
    <td></td>
  </tr>
</table><!-- /HEADER -->


<!-- BODY -->
<table class="body-wrap">
  <tr>
    <td></td>
    <td class="container" bgcolor="#FFFFFF">

      <div class="content">
      <table>
        <tr>
          <td>
            <h3>Hi,</h3>
            <p class="lead">{$fromMail} wants to connect with you.</p>
            <p>Log in on <a href="{$protocol}://app.moviesom.com/">MovieSom.com</a> and accept the connection in the app settings.</p>
            <p>Connecting with other users allows you to send and receive movie recommendations.</p>
            <!-- Callout Panel -->
            <p class="callout">
              MovieSom: Your movie sommelier. Find it at <a href="{$protocol}://{$_SERVER["SERVER_NAME"]}">MovieSom.com</a>!
            </p><!-- /Callout Panel -->
                        
            <!-- social & contact -->
            <table class="social" width="100%">
              <tr>
                <td>
                  <!-- column 1 -->
                  <table align="left" class="column">
                    <tr>
                      <td>				
                        <h5 class="">Connect with Us:</h5>
                        <p class="">
                          <a href="https://www.facebook.com/moviesomapp" class="soc-btn fb">Facebook</a>
                        </p>
                      </td>
                    </tr>
                  </table><!-- /column 1 -->	
                  
                  <!-- column 2 -->
                  <table align="left" class="column">
                    <tr>
                      <td>
                                      
                        <h5 class="">Contact Info:</h5>
                        Email: <strong><a href="emailto:webmaster@moviesom.com">webmaster@moviesom.com</a></strong></p>
                
                      </td>
                    </tr>
                  </table><!-- /column 2 -->
                  
                  <span class="clear"></span>	
                  
                </td>
              </tr>
            </table><!-- /social & contact -->
            
          </td>
        </tr>
      </table>
      </div><!-- /content -->
                  
    </td>
    <td></td>
  </tr>
</table><!-- /BODY -->

<!-- FOOTER -->
<table class="footer-wrap">
  <tr>
    <td></td>
    <td class="container">
      
        <!-- content -->
        <div class="content">
        <table>
        <tr>
          <td align="center">
            <p>
              <a href="{$protocol}://{$_SERVER["SERVER_NAME"]}/terms.php">Terms</a> |
              <a href="{$protocol}://{$_SERVER["SERVER_NAME"]}/privacy.php">Privacy</a>
            </p>
          </td>
        </tr>
      </table>
        </div><!-- /content -->
        
    </td>
    <td></td>
  </tr>
</table><!-- /FOOTER -->

</body>
</html>
EOT;
      mail($toMail, "Movie connection request", $heredocMail, $headers);

      
      
      $response['message'] = $connection;
      
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