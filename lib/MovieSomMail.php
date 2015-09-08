<?php
  require_once $_SERVER['PHP_PEAR_MAIL'];
  
  /**
   * MovieSomMail class
   */
  final class MovieSomMail
  {
    
    private $from = "MovieSom <webmaster@moviesom.com>";
    private $smtp;

    public function mailRecommendation($mailFrom, $mailTo, $tmdbId, $title, $hasSpoiler) {
      // To send HTML mail, the Content-type header must be set
      $headers = array ('From' => $this->from,
        'MIME-Version' => '1.0',
        'Content-type' => 'text/html; charset=iso-8859-1',
        'To' => $mailTo,
        'Subject' => 'Movie recommendation');

      $spoilerNotice = '';
      if($hasSpoiler) {
        $spoilerNotice = "<p class='lead'>{$mailFrom} also left a spoiler.</p>";
      }
      
      $calloutPanel = $this->getCalloutPanel();
      $social = $this->getSocial();
      $css = $this->getCss();
      $footer = $this->getFooter();
        
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
{$css}
</style>
<link rel="stylesheet" type="text/css" href="{$protocol}://www.moviesom.com/css/email.css" />

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
            <td><h6 class="collapse" style="color: white;">MovieSom</h6></td>
            <td align="right"><h6 class="collapse" style="color: #999">Recommendation</h6></td>
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
            <p class="lead">{$mailFrom} recommends you to see <a href="{$protocol}://app.moviesom.com/?tmdbMovieId={$tmdbId}">{$title}</a>.</p>
            {$spoilerNotice}
            <p>You can also log in on <a href="{$protocol}://app.moviesom.com/">MovieSom.com</a> and find the recommendation in your Personal Collection overview.</p>
            
            {$calloutPanel}
            {$social}
            
          </td>
        </tr>
      </table>
      </div><!-- /content -->
                  
    </td>
    <td></td>
  </tr>
</table><!-- /BODY -->
{$footer}
</body>
</html>

EOT;
          
      $mail = $this->smtp->send($mailTo, $headers, $heredocMail);
    }
    
    public function mailSpoilerAdded($mailFrom, $mailTo, $tmdbId, $title) {
      // To send HTML mail, the Content-type header must be set
      $headers = array ('From' => $this->from,
        'MIME-Version' => '1.0',
        'Content-type' => 'text/html; charset=iso-8859-1',
        'To' => $mailTo,
        'Subject' => 'Movie spoiler added');

      $calloutPanel = $this->getCalloutPanel();
      $social = $this->getSocial();
      $css = $this->getCss();
      $footer = $this->getFooter();
        
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
{$css}
</style>
<link rel="stylesheet" type="text/css" href="{$protocol}://www.moviesom.com/css/email.css" />

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
            <td><h6 class="collapse" style="color: white;">MovieSom</h6></td>
            <td align="right"><h6 class="collapse" style="color: #999">Spoiler</h6></td>
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
            <p class="lead">
              {$mailFrom} added a spoiler for <a href="{$protocol}://app.moviesom.com/?tmdbMovieId={$tmdbId}">{$title}</a>.
              Read it at your own risk!
            </p>
            <p>You can also log in on <a href="{$protocol}://app.moviesom.com/">MovieSom.com</a> and find the recommendation in your Personal Collection overview.</p>
            
            {$calloutPanel}
            {$social}
            
          </td>
        </tr>
      </table>
      </div><!-- /content -->
                  
    </td>
    <td></td>
  </tr>
</table><!-- /BODY -->
{$footer}
</body>
</html>

EOT;
          
      $mail = $this->smtp->send($mailTo, $headers, $heredocMail);
    }
  
    /**
     * Mail notification for points earned when movie recommendation is watched by target user.
     */
    public function mailRecommendPoints($recommender, $viewer, $tmdbId, $title, $points) {
      // To send HTML mail, the Content-type header must be set
      $headers = array ('From' => $this->from,
        'MIME-Version' => '1.0',
        'Content-type' => 'text/html; charset=iso-8859-1',
        'To' => $recommender,
        'Subject' => "You received {$points} points!");

      $calloutPanel = $this->getCalloutPanel();
      $social = $this->getSocial();
      $css = $this->getCss();
      $footer = $this->getFooter();

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
{$css}
</style>
<link rel="stylesheet" type="text/css" href="{$protocol}://www.moviesom.com/css/email.css" />

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
            <td><h6 class="collapse" style="color: white;">MovieSom</h6></td>
            <td align="right"><h6 class="collapse" style="color: #999">{$points} Points</h6></td>
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
            <p class="lead">{$viewer} has watched <a href="https://app.moviesom.com?tmdbMovieId={$tmdbId}">{$title}</a> following your recommendation.</p>
            
            ${calloutPanel}
            ${social}
            
          </td>
        </tr>
      </table>
      </div><!-- /content -->
                  
    </td>
    <td></td>
  </tr>
</table><!-- /BODY -->
{$footer}

</body>
</html>

EOT;

      $mail = $this->smtp->send($recommender, $headers, $heredocMail);
    }
    
    /**
     * Mail notification for points earned when movie recommendation spoiler is read by target user.
     */
    public function mailSpoilerReadPoints($recommender, $reader, $tmdbId, $title, $spoiler, $points) {
      // To send HTML mail, the Content-type header must be set
      $headers = array ('From' => $this->from,
        'MIME-Version' => '1.0',
        'Content-type' => 'text/html; charset=iso-8859-1',
        'To' => $recommender,
        'Subject' => "You received {$points} spoiler points!");

      $calloutPanel = $this->getCalloutPanel();
      $social = $this->getSocial();
      $css = $this->getCss();
      $footer = $this->getFooter();

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
${css}
</style>
<link rel="stylesheet" type="text/css" href="{$protocol}://www.moviesom.com/css/email.css" />

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
            <td><h6 class="collapse" style="color: white;">MovieSom</h6></td>
            <td align="right"><h6 class="collapse" style="color: #999">{$points} Points</h6></td>
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
            <p class="lead">{$reader} has read your spoiler for <a href="https://app.moviesom.com?tmdbMovieId={$tmdbId}">{$title}</a> following your recommendation.</p>
            <!-- Callout Panel -->
            <p class="callout">"
              {$spoiler}
            "</p><!-- /Callout Panel -->
            
            ${calloutPanel}
            ${social}

            
          </td>
        </tr>
      </table>
      </div><!-- /content -->
                  
    </td>
    <td></td>
  </tr>
</table><!-- /BODY -->
{$footer}

</body>
</html>

EOT;

      $mail = $this->smtp->send($recommender, $headers, $heredocMail);
    }
    
    /**
     * Mail password reset e-mail.
     */
    public function mailPasswordReset($mailTo, $token) {
        // To send HTML mail, the Content-type header must be set
      $headers = array ('From' => $this->from,
        'MIME-Version' => '1.0',
        'Content-type' => 'text/html; charset=iso-8859-1',
        'To' => $mailTo,
        'Subject' => "Password reset");

      $calloutPanel = $this->getCalloutPanel();
      $social = $this->getSocial();
      $css = $this->getCss();
      $footer = $this->getFooter();

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
${css}
</style>
<link rel="stylesheet" type="text/css" href="{$protocol}://www.moviesom.com/css/email.css" />

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
            <td><h6 class="collapse" style="color: white;">MovieSom</h6></td>
            <td align="right"><h6 class="collapse" style="color: #999">Password Reset</h6></td>
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
            <p class="lead">You received this e-mail because a password reset request has been made on MovieSom.</p>
            <p>If you have not made this request then please be aware that someone else might be trying to get entry to your account. Do not hesitate to contact us if you believe this is the case!</p>
            <p>Use the following link to reset your password:</p>
            <p><a href="{$protocol}://app.moviesom.com/passwordReset.html?resetToken={$token}">Reset password</a></p>
            <p>Note: this link is only valid for 24 hours.</p>
            ${calloutPanel}
            ${social}
            
          </td>
        </tr>
      </table>
      </div><!-- /content -->
                  
    </td>
    <td></td>
  </tr>
</table><!-- /BODY -->
{$footer}

</body>
</html>

EOT;

      $mail = $this->smtp->send($mailTo, $headers, $heredocMail);
    }
  
    public function getCalloutPanel() {
      $protocol = explode("/", $_SERVER['SERVER_PROTOCOL']);
      $protocol = strtolower(array_shift($protocol));
      if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
        $protocol = "https";
      }

      $heredocMail = <<<EOT
        <!-- Callout Panel -->
        <p class="callout">
          MovieSom: Have you seen it? Find it at <a href="{$protocol}://www.moviesom.com">MovieSom.com</a>!
          <br/>
          <a href="http://slideme.org/application/moviesom" target="_blank"><img alt="thumbnail" border="0" src="{$protocol}://www.moviesom.com/uploads/409/2015041914203379a3a92d34dd9ad2c9e7ced0ba94f2b389bdd269.png"></a>

          <a href="http://www.amazon.com/WilliM-MovieSom/dp/B00U7WNZJO/ref=sr_1_1?s=mobile-apps&amp;ie=UTF8&amp;qid=1429445566&amp;sr=1-1&amp;keywords=moviesom" target="_blank"><img alt="thumbnail" border="0" src="{$protocol}://www.moviesom.com/uploads/409/20150419141907b4c26bf2d8d2a6eeb08e0d23511288ab957c1a82.png"></a>
          
          <a href="https://marketplace.firefox.com/app/moviesom" target="_blank"><img alt="thumbnail" border="0" src="{$protocol}://www.moviesom.com/uploads/409/2015041914155926f50b484d660f90c1efaf290dbc3f775e530499.png"></a>
          
          <a href="http://willemliu.store.aptoide.com/app/market/com.moviesom/8535826/MovieSom" target="_blank"><img alt="thumbnail" border="0" src="{$protocol}://www.moviesom.com/uploads/409/20150221014505e1b8759c35a42cefdb2d7b6073de0bc717c87cb9.png"></a>
          
          <a href="http://windowsphone.com/s?appId=da583882-69a1-4228-bce2-ad94327642a6" target="_blank"><img alt="thumbnail" border="0" src="{$protocol}://www.moviesom.com/uploads/409/20150221004838a9f7e54c94f63044a684330cdc88523d0bb7f383.png"></a>
          
          <a href="https://play.google.com/store/apps/details?id=com.moviesom" target="_blank"><img alt="thumbnail" border="0" src="{$protocol}://www.moviesom.com/uploads/409/20150221004837c7377bfeed1711725186a950b3d8657a6733eb2e.png"></a>
          
          <a href="{$protocol}://app.moviesom.com" target="_blank"><img alt="thumbnail" border="0" src="{$protocol}://www.moviesom.com/uploads/409/2015022523384041c3a0f1c0513ff5dafceb957f6f2579ff0d515f.png"></a>
          
          <a href="{$protocol}://app.moviesom.com" target=""><img alt="thumbnail" border="0" src="{$protocol}://www.moviesom.com/uploads/409/20150422193219a1f40ac9bb9a558d8dfd3dc8bd06e7da65066cd9.png"></a>
        </p><!-- /Callout Panel -->
EOT;
      
      return $heredocMail;
    }
    
    public function getSocial() {
      $protocol = explode("/", $_SERVER['SERVER_PROTOCOL']);
      $protocol = strtolower(array_shift($protocol));
      if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
        $protocol = "https";
      }

      $heredocMail = <<<EOT
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
EOT;
      
      return $heredocMail;
    }
    
    public function getCss() {
      $protocol = explode("/", $_SERVER['SERVER_PROTOCOL']);
      $protocol = strtolower(array_shift($protocol));
      if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
        $protocol = "https";
      }

      $heredocMail = <<<EOT
        *{margin:0;padding:0}*{font-family:"Helvetica Neue","Helvetica",Helvetica,Arial,sans-serif}img{max-width:100%}.collapse{margin:0;padding:0}body{-webkit-font-smoothing:antialiased;-webkit-text-size-adjust:none;width:100%!important;height:100%}a{color:#2ba6cb}.btn{text-decoration:none;color:#fff;background-color:#666;padding:10px 16px;font-weight:bold;margin-right:10px;text-align:center;cursor:pointer;display:inline-block}p.callout{padding:15px;background-color:#ecf8ff;margin-bottom:15px}.callout a{font-weight:bold;color:#2ba6cb}table.social{background-color:#ebebeb}.social .soc-btn{padding:3px 7px;font-size:12px;margin-bottom:10px;text-decoration:none;color:#fff;font-weight:bold;display:block;text-align:center}a.fb{background-color:#3b5998!important}a.tw{background-color:#1daced!important}a.gp{background-color:#db4a39!important}a.ms{background-color:#000!important}.sidebar .soc-btn{display:block;width:100%}table.head-wrap{width:100%}.header.container table td.logo{padding:15px}.header.container table td.label{padding:15px;padding-left:0}table.body-wrap{width:100%}table.footer-wrap{width:100%;clear:both!important}.footer-wrap .container td.content p{border-top:1px solid #d7d7d7;padding-top:15px}.footer-wrap .container td.content p{font-size:10px;font-weight:bold}h1,h2,h3,h4,h5,h6{font-family:"HelveticaNeue-Light","Helvetica Neue Light","Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif;line-height:1.1;margin-bottom:15px;color:#000}h1 small,h2 small,h3 small,h4 small,h5 small,h6 small{font-size:60%;color:#6f6f6f;line-height:0;text-transform:none}h1{font-weight:200;font-size:44px}h2{font-weight:200;font-size:37px}h3{font-weight:500;font-size:27px}h4{font-weight:500;font-size:23px}h5{font-weight:900;font-size:17px}h6{font-weight:900;font-size:14px;text-transform:uppercase;color:#444}.collapse{margin:0!important}p,ul{margin-bottom:10px;font-weight:normal;font-size:14px;line-height:1.6}p.lead{font-size:17px}p.last{margin-bottom:0}ul li{margin-left:5px;list-style-position:inside}ul.sidebar{background:#ebebeb;display:block;list-style-type:none}ul.sidebar li{display:block;margin:0}ul.sidebar li a{text-decoration:none;color:#666;padding:10px 16px;margin-right:10px;cursor:pointer;border-bottom:1px solid #777;border-top:1px solid #fff;display:block;margin:0}ul.sidebar li a.last{border-bottom-width:0}ul.sidebar li a h1,ul.sidebar li a h2,ul.sidebar li a h3,ul.sidebar li a h4,ul.sidebar li a h5,ul.sidebar li a h6,ul.sidebar li a p{margin-bottom:0!important}.container{display:block!important;max-width:600px!important;margin:0 auto!important;clear:both!important}.content{padding:15px;max-width:600px;margin:0 auto;display:block}.content table{width:100%}.column{width:300px;float:left}.column tr td{padding:15px}.column-wrap{padding:0!important;margin:0 auto;max-width:600px!important}.column table{width:100%}.social .column{width:280px;min-width:279px;float:left}.clear{display:block;clear:both}@media only screen and (max-width:600px){a[class="btn"]{display:block!important;margin-bottom:10px!important;background-image:none!important;margin-right:0!important}div[class="column"]{width:auto!important;float:none!important}table.social div[class="column"]{width:auto!important}}
EOT;
      
      return $heredocMail;
    }
    
    public function getFooter() {
      $protocol = explode("/", $_SERVER['SERVER_PROTOCOL']);
      $protocol = strtolower(array_shift($protocol));
      if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
        $protocol = "https";
      }

      $heredocMail = <<<EOT
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
                      <a href="{$protocol}://www.moviesom.com/resources/20150825195537terms.txt">Terms</a> |
                      <a href="{$protocol}://www.moviesom.com/resources/20150303203238privacy.txt">Privacy</a>
                    </p>
                  </td>
                </tr>
              </table>
                </div><!-- /content -->
                
            </td>
            <td></td>
          </tr>
        </table><!-- /FOOTER -->
EOT;
      
      return $heredocMail;
    }
  
    public function setSmtp($smtp) {
      $this->smtp = $smtp;
    }
  
    /**
     * Constructor
     */
    public function __construct()
    {
      $this->smtp = @Mail::factory('smtp', array ('host' => $_SERVER['PHP_SMTP_SERVER'],
        'port' => $_SERVER['PHP_SMTP_PORT'],
        'auth' => true,
        'username' => $_SERVER['PHP_SMTP_USERNAME'],
        'password' => $_SERVER['PHP_SMTP_PASSWORD']));
    }
  }
?>
