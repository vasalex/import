<?php
	require 'config.class.php';
	require 'elasticSearch.class.php';
	$e = new ElasticSearch;
	$e->index = 'meteonova';
	$type = 'countries';
	// пример запроса по названию страны на русском языке
	$result = json_decode($e->query($type, 'capital.names.name_ru:Москва'));
	if (isset($result->error)) {
		print json_encode($result->error);
		die();
	}
	if ($result == NULL) {
		print "{'error': 'fatal'}";
		die();
	}
	foreach ($result->hits->hits as $item) {
		print "\t{$item->_source->names->name_ru}\n";
	}
	print "\t/------------------/\n";

	$type = 'regions';
	// пример запроса по индексу страны на руссском языке
	$result = json_decode($e->query($type, 'country.id:156', '{"from":0, "size":10000, "sort": [{ "names.name_ru.raw" : {"order" : "asc"}},"_score"]}'));
	if (isset($result->error)) {
		print json_encode($result->error);
		die();
	}
	if ($result == NULL) {
		print "{'error': 'fatal'}";
		die();
	}		
	foreach ($result->hits->hits as $item) {
		print "\t{$item->_source->names->name_ru}\n";
	}
	print "\t/------------------/\n";

	$type = 'municipal';
	$result = json_decode($e->query($type, 'region.id:50', '{"from":0, "size":10000, "sort": [{ "names.name_ru.raw" : {"order" : "asc"}},"_score"]}'));
	if (isset($result->error)) {
		print json_encode($result->error);
		die();
	}
	if ($result == NULL) {
		print "{'error': 'fatal'}";
		die();
	}		
	foreach ($result->hits->hits as $item) {
		print "\t{$item->_source->names->name_ru}\n";
	}
	print "\t/------------------/\n";

	$type = 'cities';
	//$result = json_decode($e->query($type, 'region.id:52', '{"from":0, "size":10000, "sort": [{ "names.name_ru.raw" : {"order" : "asc"}},"_score"]}'));
	$result = json_decode($e->query2($type, '{"from":0, "size":10000, 
												    "query": {
												        "filtered": {
												            "query": {
												                "match_all": {}
												            },
												            "filter": {
													            "geo_distance" : {
												                	"distance" : "12km",
												                	"pin.location" : {
												                    	"lat" : 53,
												                    	"lon" : 37
												                	}
												            	}
												            }
												        }
												    }
												}'));	
	if (isset($result->error)) {
		print json_encode($result->error);
		die();
	}
	if ($result == NULL) {
		print "{'error': 'fatal'}";
		die();
	}		
	foreach ($result->hits->hits as $item) {
		print "\t{$item->_source->names->name_ru}\n";
	}		
?>