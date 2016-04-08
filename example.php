<?php
	require 'config.class.php';
	require 'elasticSearch.class.php';
	$e = new ElasticSearch;
	$e->index = 'meteonova';
	$type = 'countries';
	// пример запроса по названию страны на русском языке
	/*$result = json_decode($e->query($type, 'capital.names.name_ru:Москва'));
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
*/
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
												                	"distance" : "20km",
												                	"pin.location" : {
												                    	"lat" : 56.3,
												                    	"lon" : 44
												                	}
												            	}
												            }
												        }
												    },
												    "sort": [{ "names.name_ru.raw" : {"order" : "asc"}},"_score"]
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
		print "\t{$item->_source->names->name_ru}, {$item->_source->country->names->name_ru}, {$item->_source->region->names->name_ru}, {$item->_source->municipal->names->name_ru}\n";

	}
	print "\t/------------------/\n";
	//$result = json_decode($e->query($type, 'region.id:52', '{"from":0, "size":10000, "sort": [{ "names.name_ru.raw" : {"order" : "asc"}},"_score"]}'));
	/*$result = json_decode($e->query2($type, '{"from":0, "size":10, 
												   "query": {
												      "query_string": {
												         "default_field": "names.name_ru",
												         "query": "александровк*",
												         "minimum_should_match": "100%"
												      }
												   }
												}'));	
	*/
	$query = '';
	if (isset($_GET['q'])) {
		$query = '('.($_GET['q']).') AND ';
		$strings = explode(" ", $_GET['q']);
		while (count($strings)>0) {
			$subguery = '(';
			for ($j = 0; $j < count($strings); $j++) {
				$subguery.= ($strings[$j] . "* ");	
			}
			array_shift($strings);
			$subguery .= (')'.(count($strings)>0?' AND ':''));
			$query .= $subguery;
		}			
	}
	$result = json_decode($e->query2($type, '{"from":0, "size":10,
												   "query": {
												      "query_string": {
												         "fields": ["names.name_ru", "region.names.name_ru", "country.names.name_ru", "municipal.names.name_ru"],
												         "query": "'.$query.'"
												      }
												   }
												}'));	
	
	//print json_encode($result);
	if (isset($result->error)) {
		print json_encode($result->error);
		die();
	}
	if ($result == NULL) {
		print "{'error': 'fatal'}";
		die();
	}		
	foreach ($result->hits->hits as $item) {
		print "\t{$item->_source->names->name_ru}, {$item->_source->country->names->name_ru}, {$item->_source->region->names->name_ru}, {$item->_source->municipal->names->name_ru}\n";
	}
?>