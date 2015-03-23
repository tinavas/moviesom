<?php
  require_once('lib/config.php');

  header('HTTP/1.1 404 NOT FOUND');
  $uri = "";
  $description = "Keep track of the seen and unseen";
  if(isset($_REQUEST["description"])) {
    $description = str_replace('"', '&quot;', $_REQUEST["description"]);
  }
  $meta = ["title" => "MovieSom - Your movie sommelier",
    "image" => "http://www.moviesom.com/resources/20150216211140social.jpg",
    "description" => $description
  ];
  if(isset($_REQUEST["tmdbMovieId"])) {
    $uri = "?tmdbMovieId={$_REQUEST["tmdbMovieId"]}";
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }
      $stmt = $dbh->prepare("SELECT * FROM movies AS m 
                              JOIN movie_sources AS ms ON ms.movie_id=m.id
                              WHERE ms.tmdb_id=:tmdb_id");
      $stmt->bindParam(":tmdb_id", $_REQUEST["tmdbMovieId"]);
      $stmt->execute();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $meta["title"] = str_replace('"', '&quot;', $row["title"]);
        $meta["image"] = "https://image.tmdb.org/t/p/original/{$row["backdrop_path"]}";
      }
      
      
      if($dbh->commit()) {
        header('HTTP/1.1 200 OK');
      }
    }
    catch(PDOException $e) {  
      echo "<!--";
      echo $e;
      echo "--!>";
    }

  } else if(isset($_REQUEST["tmdbTvId"])) {
    $uri = "?tmdbTvId={$_REQUEST["tmdbTvId"]}";
    try {
      $dbh = $db->connect();
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($dbh->inTransaction() === false) {
        $dbh->beginTransaction();
      }
      $stmt = $dbh->prepare("SELECT * FROM tv
                              JOIN tv_sources AS ts ON ts.tv_id=tv.id
                              WHERE ts.tmdb_id=:tmdb_id");
      $stmt->bindParam(":tmdb_id", $_REQUEST["tmdbTvId"]);
      $stmt->execute();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $meta["title"] = str_replace('"', '&quot;', $row["title"]);
        $meta["image"] = "https://image.tmdb.org/t/p/original/{$row["backdrop_path"]}";
      }
      
      
      if($dbh->commit()) {
        header('HTTP/1.1 200 OK');
      }
    }
    catch(PDOException $e) {  
      echo "<!--";
      echo $e;
      echo "--!>";
    }
  }
  
  $protocol = explode("/", $_SERVER['SERVER_PROTOCOL']);
  $protocol = strtolower(array_shift($protocol));
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
    $protocol = "https";
  }

$html = <<<EOT
<!doctype html>
<html class="no-js" lang="en" style="overflow: hidden;">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <meta property="og:title" content="{$meta['title']}"/>
    <meta property="og:image" content="{$meta['image']}"/>
    <meta property="og:site_name" content="MovieSom - Your movie sommelier"/>
    <meta property="og:description" content="{$meta['description']}"/>
    <meta property="og:url" content="{$protocol}//app.moviesom.com/{$uri}"/>
    <link rel="image_src" href="{$meta['image']}" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{$meta['description']}"/>
    <meta name="keywords" content="MovieSom, WilliM, willem liu, moviedb, tmdb, imdb, rottentomatoes, themoviedb, the movie db, trailers, movie trailers, movie information, movie info">
    <link href="/img/favicon.ico" rel="shortcut icon" />

    <title>{$meta['title']} - Movie Sommelier</title>
    <link rel="stylesheet" type="text/css" href="css/styles.css" />
    <script src="js/lib/modernizr.js"></script>
  </head>
  <body>
    <iframe src="{$protocol}//app.moviesom.com/{$uri}" frameborder="0" height="100%" width="100%"/>
    <script type="text/javascript" data-main="js/config" src="js/require.js"></script>
  </body>
</html>
EOT;
echo $html;
echo "<!-- page: {$_SERVER['PHP_SELF']} -->\r\n";
echo "<!-- " . $execTime->getTime() . "ms -->\r\n";
echo "<!-- DB queries: " . $db->getQueryCount() . " -->\r\n";
