<?php
	require 'config.class.php';
	require 'elasticSearch.class.php';
	$e = new ElasticSearch;
	$e->index = 'meteonova'; // name of the index
	$e->create(); // create the index
	$type = 'regions'; // name of the data items
	$data_structure = '{"regions": {
						"properties": {
							"id":{"type":"string"},
							"name": {"type":"string","fields":{"raw":{"type":"string","index":"not_analyzed"}}},							
							"names":{"properties":{
								"name_ru":{"type":"string","fields":{"raw":{"type":"string","index":"not_analyzed"}}},
								"name_en":{"type":"string","fields":{"raw":{"type":"string","index":"not_analyzed"}}},
								"name_ua":{"type":"string","fields":{"raw":{"type":"string","index":"not_analyzed"}}}
							}},
							"country": {
								"properties": {
									"id":{"type":"string"},
									"name":{"type":"string"},
									"names":{"properties":{
										"name_ru":{"type":"string"},
										"name_en":{"type":"string"},
										"name_ua":{"type":"string"}
									}}										
								}
							},							
							"capital": {
								"properties": {
									"id":{"type":"string"},
									"name":{"type":"string"},
									"names":{"properties":{
										"name_ru":{"type":"string"},
										"name_en":{"type":"string"},
										"name_ua":{"type":"string"}
									}}										
								}
							} 
						}
					}}';	
	// маппим структуру таблицы
	$result = json_decode($e->map($type, $data_structure));
	if (isset($result->error)) {
		print "\t".json_encode($result->error)."\n";
		die();
	}
	//$e->close();
	// устанавливаваем настройки
	//$e->settings();
	//$e->open();
    // делаем запрос в базу данных за списком стран
	try {
	  $db = new PDO("mysql:host=".Config::$DB_SERVER.";dbname=".Config::$DB_NAME, Config::$DB_USERNAME, Config::$DB_PASSWORD);
	  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	  $db->exec("set names utf8");
	}
	catch(PDOException $e) {
	    echo $e->getMessage();
	    die;
	}    		
	$sql = 'SELECT regions.*, countries.*, Town.name_en as cityname_en, Town.name_ru as cityname_ru, Town.name_ua as cityname_ua FROM (regions INNER JOIN countries ON regions.nCountry = countries.cIndex) INNER JOIN Town ON regions.capital_s = Town.index;';
	$sth = $db->query($sql);
	$j = 0;
	$k = 0;
	while ($row = $sth->fetch()) {	
		// пытаемся добавить запись в индекс
		$putString = '{
			"id":"'.$row['state'].'",
			"name": "'.$row['EN'].'",
			"names": {
				"name_ru":"'.$row['RU'].'", 
				"name_en": "'.$row['EN'].'", 
				"name_ua":"'.$row['UA'].'"
			}, 
			"country": {
				"id":"'.$row['cIndex'].'",
				"name": "'.$row['name_en'].'",
				"names": {
					"name_ru":"'.$row['name_ru'].'", 
					"name_en": "'.$row['name_en'].'", 
					"name_ua":"'.$row['name_ua'].'"
				}				 	
			},			
			"capital": {
				"id":"'.$row['capital_s'].'",
				"name": "'.$row['cityname_en'].'",
				"names": {
					"name_ru":"'.$row['cityname_ru'].'", 
					"name_en": "'.$row['cityname_en'].'", 
					"name_ua":"'.$row['cityname_ua'].'"
				}				 	
			}
		}';			
		$result = json_decode($e->add($type, $row['state'], $putString));
		if (isset($result->error)) {
			print "\t".json_encode($result->error)."\n";
			continue;
		}
		if ($result->created === true) { // страна добавлена в индекс
			$j++;
		}
		else { // если есть в индексе, то пытаемся обновить
			$result = json_decode($e->update($type, $row['state'], '{"doc": '.$putString.'}'));
			if (!isset($result->error) && $result != NULL) { // проверяем удалось ли обновить запись в индексе
				$k++;	
			}
		}  	
	}
	// выводим сколько добавлено записей, сколько обновлено
	print ("\t".$j." regions was added\n");
	print ("\t".$k." regions was updated\n");
	
?>