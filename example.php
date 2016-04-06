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
	foreach ($result->hits->hits as $item) {
		print "\t{$item->_source->names->name_ru}\n";
	}	
	$type = 'regions';
	// пример запроса по индексу страны на руссском языке
	//$type = 'regions' 
	$result = json_decode($e->query($type, 'country.id:156', '{"from":0, "size":10000, "sort": [{ "names.name_ru.raw" : {"order" : "asc"}},"_score"]}'));
	if (isset($result->error)) {
		print json_encode($result->error);
		die();
	}	
	foreach ($result->hits->hits as $item) {
		print "\t{$item->_source->names->name_ru}\n";
	}
?>