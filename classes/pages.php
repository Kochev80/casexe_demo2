<?php

// Вывод шаблонов страниц
class Pages {

    // Страница для игры
    public static function renderPlayPage($f3) {
        $f3->set('content','play.htm');
        echo View::instance()->render('layout.htm');
    }

    // Страница для администратора
    public static function renderAdminPage() {
        // TODO: здесь должен быть код для рендера шаблона админки
    }

}

?>