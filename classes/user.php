<?php

// Класс для действий с пользователем
class User {

    // Авторизация пользователя
    public static function login($f3) {
        if ($f3->exists('POST.login') && $f3->exists('POST.pass')) {
            $rows=$f3->get('db')->exec(
                'select * from users where login=:login and pass=password(:pass) limit 1',
                array(':login' => $f3->get('POST.login'), ':pass' => $f3->get('POST.pass')));
            if (count($rows)==1) {
                $f3->clear('SESSION.baraban'); // На всякий случай очистка старых подборок на барабан
                $f3->set('SESSION.user_id',$rows[0]['id']);
                Play::getState($f3); // В случае удачной авторизации сразу вернем текущее состояние игры
            } else response::error('Неправильный логин или пароль.');
        } else response::error('Не указан логин и/или пароль.');
    }

    // Выход пользователя
    public static function logout($f3) {
        $f3->set('SESSION.user_id',0);
        Play::getState($f3);
    }

}

?>