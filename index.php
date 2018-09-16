<?php

// Подключение фреймворка
$f3=require_once('lib/base.php');
$f3->set('DEBUG',1);
if ((float)PCRE_VERSION<7.9)
	trigger_error('PCRE version is out of date');
$f3->config('config.ini');

// Путь к обработчикам
$f3->set('AUTOLOAD','classes/');

// Подключение БД
$f3->set('db',new \DB\SQL('mysql:host=localhost;port=3306;dbname=casexe','root',''));

// Сессия
new \DB\SQL\Session($f3->get('db'));

// Маршруты
require_once('routes/routes.php');

$f3->run();
