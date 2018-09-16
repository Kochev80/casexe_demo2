<?php

// Отправка сообщений с заголовками
class Response {

    // Возвращает массив данных для javascript
    public static function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    // Возвращает ошибку
    public static function error($message) {
        self::json(['message'=>$message]);
    }

}

?>