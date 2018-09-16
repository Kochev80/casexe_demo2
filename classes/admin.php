<?php

class Admin {

    // Отправка денег на карты
    public static function sendMoney($f3) {
        $limit=$f3->get('GET.limit');
        if ($limit>0) {
            // Запрашиваем указанное число пользователей, для которых необходим перевод
            $rows=$f3->get('db')->exec('select id,card,money_out from users where money_out>0 limit :limit',array(':limit'=>$limit));
            foreach ($rows as $row) {
                $success=false;
                // ........................
                // тут какой-нибудь http_request в банк, номер карты в $row["card"], результат в $success
                // ........................
                if ($success) {
                    // Фиксируем в логе
                    $f3->get('db')->exec('insert into payments (id_user,money,tms) values (:id_user,:money,now())',array(':id_user'=>$row["id"],':money'=>$row["money_out"]));
                    // Вычитаем деньги со счета
                    $f3->get('db')->exec('update users set money_out=money_out-:money where id=:id_user',array(':id_user'=>$row["id"],':money'=>$row["money_out"]));
                }
            }
        }
    }

    public static function getDeliveryList() {
        // TODO
    }

    public static function getMoneyTransferList() {
        // TODO
    }


}

?>