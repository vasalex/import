<?php

// http://www.elasticsearch.com/docs/elasticsearch/rest_api/

class ElasticSearch {
  public $index;

  function __construct($server = 'http://localhost:9200'){
    $this->server = $server;
  }

  function call($path, $http = array("method"=>'PUT', "content"=>'')) {
    if (!isset($http["content"])) $http["content"] = "";
    if (!$this->index) throw new Exception('$this->index needs a value');  
    $query = 'curl -X'.$http["method"].' "'.$this->server.'/'.$this->index.'/'.$path.'" -d "'.str_replace('"', '\"', str_replace(array("\n","\r"), '', $http["content"])).'"';
    //echo($query);
    return exec($query);
    //echo 'done '.PHP_EOL;
  }

  //curl -X PUT http://localhost:9200/{INDEX}/
  function create(){
     $this->call(NULL, array('method' => 'PUT'));
  }

  //curl -X DELETE http://localhost:9200/{INDEX}/
  function drop(){
     $this->call(NULL, array('method' => 'DELETE'));
  }

  //curl -X GET http://localhost:9200/{INDEX}/_status
  function status(){
    return $this->call('_status');
  }

  //curl -X GET http://localhost:9200/{INDEX}/{TYPE}/_count -d {matchAll:{}}
  function count($type){
    return $this->call($type . '/_count', array('method' => 'GET'));
  }

  function close() {
    return $this->call('_close', array('method' => 'POST'));  
  }

  function open() {
    return $this->call('_open', array('method' => 'POST'));  
  }  

  //curl -X PUT http://localhost:9200/{INDEX}/{TYPE}/_mapping -d ...
  function map($type, $data){
    return $this->call($type . '/_mapping', array('method' => 'PUT', 'content' => $data));
  }

  function settings($data = '') {
    $data = '{"settings": {"analysis": {"filter": {"russian_stop": {"type":"stop","stopwords":  "_russian_" },"russian_stemmer": {"type":"stemmer","language":"russian"}},"analyzer": {"russian": {"tokenizer":  "standard","filter": ["lowercase","russian_stop","russian_stemmer"]}}}}}';
    return $this->call('_settings', array('method' => 'PUT', 'content' => $data));
  }

  //curl -X PUT http://localhost:9200/{INDEX}/{TYPE}/{ID} -d ...
  function add($type, $id, $data) {
    return $this->call($type . '/' . $id, array('method' => 'PUT', 'content' => $data));
  }

  //curl -X POST http://localhost:9200/{INDEX}/{TYPE}/{ID}/_update -d ...
  function update($type, $id, $data) {
    return $this->call($type . '/' . $id . '/_update', array('method' => 'POST', 'content' => $data));    
  }

  //curl -X GET http://localhost:9200/{INDEX}/{TYPE}/_search?q= ...
  function query($type, $q, $data = ''){
    return $this->call($type . '/_search?' . http_build_query(array('q' => $q)), array('method' => 'GET', 'content' => $data));
  }

  function query2($type, $data = ''){
    return $this->call($type . '/_search', array('method' => 'GET', 'content' => $data));
  }  
}