<?php
	require 'config.class.php';
	require 'elasticSearch.class.php';
	$e = new ElasticSearch;
	$e->index = 'meteonova'; // name of the index
	$e->create(); // create the index
	$type = 'municipal'; // name of the data items
	$data_structure = '{"'.$type.'": {
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
							"region": {
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
	$sql = 'SELECT municipal.*, regions.RU as regionname_ru, regions.EN as regionname_en, regions.UA as regionname_ua, Town.name_ru as cityname_ru, Town.name_en as cityname_en, Town.name_ua as cityname_ua, countries.cIndex, countries.name_ru, countries.name_en, countries.name_ua FROM ((municipal INNER JOIN regions ON municipal.state = regions.state) INNER JOIN Town ON municipal.capital_m = Town.index) INNER JOIN countries ON regions.nCountry = countries.cIndex';
	$sth = $db->query($sql);
	$i = 0;
	$j = 0;
	$k = 0;
	while ($row = $sth->fetch()) {	
		// пытаемся добавить запись в индекс
		$pushString = '{
			"id":"'.$row['nMun'].'",
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
			"region": {
				"id":"'.$row['state'].'",
				"name": "'.$row['regionname_en'].'",
				"names": {
					"name_ru":"'.$row['regionname_ru'].'", 
					"name_en": "'.$row['regionname_en'].'", 
					"name_ua":"'.$row['regionname_ua'].'"
				}				 	
			},						
			"capital": {
				"id":"'.$row['capital_m'].'",
				"name": "'.$row['cityname_en'].'",
				"names": {
					"name_ru":"'.$row['cityname_ru'].'", 
					"name_en": "'.$row['cityname_en'].'", 
					"name_ua":"'.$row['cityname_ua'].'"
				}				 	
			}
		}';			
		$result = json_decode($e->add($type, $row['nMun'], $pushString));
		if (isset($result->error)) {
			print "\t".json_encode($result->error)."\n";
			continue;
		}
		if ($result->created === true) { // страна добавлена в индекс
			$j++;
		}
		else { // если есть в индексе, то пытаемся обновить
			$result = json_decode($e->update($type, $row['nMun'], '{"doc": '.$pushString.'}'));
			if (!isset($result->error) && $result != NULL) { // проверяем удалось ли обновить запись в индексе
				$k++;	
			}
		} 
		//if ($i == 0) break; 	
	}
	// выводим сколько добавлено записей, сколько обновлено
	print ("\t".$j." municipal was added\n");
	print ("\t".$k." municipal was updated\n");
	
?>