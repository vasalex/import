<?php
	require 'config.class.php';
	require 'elasticSearch.class.php';
	$e = new ElasticSearch;
	$e->index = 'meteonova'; // name of the index
	$e->create(); // create the index
	$type = 'countries'; // name of the data items
	// маппим структуру таблицы
	$e->map($type, '{\"countries\": {\"properties\": {\"id\":{\"type\": \"string\"},\"names\":{\"properties\":{\"name_ru\": {\"type\": \"string\"},\"name_en\": {\"type\": \"string\"},\"name_ua\": {\"type\": \"string\"}}},\"name\": {\"type\": \"string\"}}}}'); 
	$e->close();
	// устанавливаваем настройки
	$e->settings('{\"settings\": {\"analysis\": {\"filter\": {\"russian_stop\": {\"type\":\"stop\",\"stopwords\":  \"_russian_\" },\"russian_stemmer\": {\"type\":       \"stemmer\",\"language\":\"russian\"}},\"analyzer\": {\"russian\": {\"tokenizer\":  \"standard\",\"filter\": [\"lowercase\",\"russian_stop\",\"russian_stemmer\"]}}}}}');
	$e->open();
	//$bits = 8 * PHP_INT_SIZE;
	//echo "(Info: This script is running as $bits-bit.)\r\n\r\n";

	/*$connStr = 'odbc:Driver={Driver do Microsoft Access (*.mdb)}; Dbq=F:\\MN\\cityru.mdb; charset=utf8';
	try{
		$dbh = new PDO($connStr); 	
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	
	} catch(PDOException $e){
		echo 'ERROR: ' . $e->getMessage();
		exit;
    } */ 

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
	$sql = 'SELECT * FROM countries';
	$sth = $db->query($sql);
	$j = 0;
	$k = 0;
	while ($row = $sth->fetch()) {	
		// пытаемся добавить запись в индекс	
		$result = json_decode($e->add($type, $row['cIndex'], '{\"id\":\"'.$row['cIndex'].'\",\"name\": \"'.$row['name_en'].'\",\"names\": {\"name_ru\":\"'.$row['name_ru'].'\", \"name_en\": \"'.$row['name_en'].'\", \"name_ua\":\"'.$row['name_ua'].'\"}}'));
		if ($result->created === true) { // страна добавлена в индекс
			print "\tSuccess: country ".$row['name_en']." was added\n";
			$j++;
		}
		else { //если не добавлена в индекс, то пыьаемся обновить
			$result = json_decode($e->update($type, $row['cIndex'], '{\"doc\": {\"id\":\"'.$row['cIndex'].'\",\"name\": \"'.$row['name_en'].'\",\"names\": {\"name_ru\":\"'.$row['name_ru'].'\", \"name_en\": \"'.$row['name_en'].'\", \"name_ua\":\"'.$row['name_ua'].'\"}}}'));
			if (!isset($result->error) || $result != NULL) { // проверяем удалось ли одновить запись в индексе
				$k++;	
			}
		}  	
	}
	// выводим сколько добавлено записей, сколько обновлено
	print ("\t".$j." items was added\n");
	print ("\t".$k." items was updated\n");
	
	// пример запроса по названию страны на руссском языке
	/*$result = json_decode($e->query($type, 'names.name_ru:россия'));
	foreach ($result->hits->hits as $item) {
		var_dump($item->_source);
		print "\t{$item->_source->names->name_ru}\n";
	}*/
?>