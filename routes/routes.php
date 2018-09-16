<?php

// Обработчики на страницы
$f3->route('GET /',                 'Pages::renderPlayPage');   // Основная страница для розыгрыша

// Обработчики на AJAX запросы
$f3->route('POST /api/login',       'User::login');             // Авторизация
$f3->route('POST /api/logout',      'User::logout');            // Деавторизация
$f3->route('GET  /api/get_state',   'Play::getState');          // Возвращает текущую ситуацию
$f3->route('GET  /api/get_prize',   'Play::getPrize');          // Получение приза
$f3->route('POST /api/money2card',  'Play::moveMoneyToCard');   // Пометить выигрыш к выводу
$f3->route('POST /api/money2bonus', 'Play::moveMoneyToBonus');  // Перевести деньги в бонусы
$f3->route('POST /api/item2post',   'Play::moveItemToPost');    // Пометить предмет к отправке
$f3->route('POST /api/item2money',  'Play::moveItemToMoney');   // Продать предмет
