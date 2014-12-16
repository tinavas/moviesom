<?php
  error_reporting(E_ALL);
  ini_set("display_errors", 1);
  require 'vendor/autoload.php';
  
  $elasticaClient = new \Elastica\Client(array(
    'host' => '127.0.0.1'
  ));
  
  // Load index
  $elasticaIndex = $elasticaClient->getIndex('indexname');
  $elasticaIndex->create([], true);
  $elasticaType = $elasticaIndex->getType('external');
  
  echo "=============<br>\r\n";
  // Set mapping
  $mapping = new \Elastica\Type\Mapping();
  $mapping->setType($elasticaType);
  $mapping->setProperties([
    'name' => ['type' => 'string'],
    'tag' => [
      'type' => 'string',
      'index' => 'not_analyzed',
    ],
    '_boost' => ['type' => 'float']
  ]);
  $mapping->send();
  
  // The Id of the document
  $id = 1;
  // Create a document
  $doc = [
      'id' => $id,
      'name' => 'John Doe',
      'tags' => [
          'mewantcookie',
          'Cookie Monster'
      ],
      '_boost'  => 1.0
  ];
  // First parameter is the id of document.
  $document = new \Elastica\Document($id, $doc);
  $elasticaType->addDocument($document);
  $elasticaIndex->refresh();

  echo "=============<br>\r\n";
  // Raw JSON call
  $query =<<<EOT
  {
    "query": {
      "query_string": {
        "query":"John Doe"
      }
    }
  }
EOT;

  $path = $elasticaIndex->getName() . '/' . $elasticaType->getName() . '/_search';

  $response = $elasticaClient->request($path, \Elastica\Request::GET, $query);
  var_dump( $response->getData() );
  
  echo "=============<br>\r\n";
  $queryObject = new \Elastica\Query($query);
  $percolator = new \Elastica\Percolator($elasticaIndex);
  var_dump($percolator->registerQuery("queryID", $queryObject));
  echo "=============<br>\r\n";
  var_dump($percolator->matchDoc($document));
  $percolator->unregisterQuery("queryID");
