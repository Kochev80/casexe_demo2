<?php

// Розыгрыш призов
class Play {

    // Возвращает значение единичного параметра
    public static function getParam($f3,$name) {
        $rows=$f3->get('db')->exec(
            'select value from params where name=:name',
            array(':name'=>$name));
        if (count($rows)>0) return $rows[0]['value']; else return 0;
    }

    // Отметить денежный выигрыш к выводу на карту
    public static function moveMoneyToCard($f3) {
        if ($f3->exists('SESSION.user_id') && $f3->get('SESSION.user_id')>0) {
            $f3->get('db')->exec(
                'update users set money_out=money_out+money_win,money_win=0 where id=:id',
                array(':id'=>$f3->get('SESSION.user_id')));
        }
        self::getState($f3);
    }

    // Перевести денежный выигрыш в бонусы
    public static function moveMoneyToBonus($f3) {
        if ($f3->exists('SESSION.user_id') && $f3->get('SESSION.user_id')>0) {
            $f3->get('db')->exec(
                'update users set bonus=bonus+money_win*:rate,money_win=0 where id=:id',
                array(':id'=>$f3->get('SESSION.user_id'),':rate'=>self::getParam($f3,'money2bonus')));
        }
        self::getState($f3);
    }

    // Пометить предмет на отправку
    public static function moveItemToPost($f3) {
        if ($f3->exists('SESSION.user_id') && $f3->get('SESSION.user_id')>0 && $f3->exists('POST.id')) {
            $f3->get('db')->exec(
                'update delivery set fl_accept=1 where id_user=:id_user and id=:id',
                array(':id_user'=>$f3->get('SESSION.user_id'),':id'=>$f3->get('POST.id')));
        }
        self::getState($f3);
    }

    // Поменять предмет на деньги
    public static function moveItemToMoney($f3) {
        if ($f3->exists('SESSION.user_id') && $f3->get('SESSION.user_id')>0 && $f3->exists('POST.id')) {
            $rows=$f3->get('db')->exec(
                'select i.cost,i.id from delivery as d inner join items as i on d.id_item=i.id where d.id_user=:id_user and d.id=:id',
                array(':id_user'=>$f3->get('SESSION.user_id'),':id'=>$f3->get('POST.id')));
            $cost=$rows[0]['cost']*self::getParam($f3,'item2money');
            $id_item=$rows[0]['id'];
            $f3->get('db')->exec(
                'update users set money_win=money_win+:cost where id=:id_user',
                array(':id_user'=>$f3->get('SESSION.user_id'),':cost'=>$cost));
            $f3->get('db')->exec(
                'delete from delivery where id_user=:id_user and id=:id',
                array(':id_user'=>$f3->get('SESSION.user_id'),':id'=>$f3->get('POST.id')));
            $f3->get('db')->exec(
                'update items set amount=amount+1 where id=:id_item',
                array(':id_item'=>$id_item));
        }
        self::getState($f3);
    }

    // Возвращает текущую игровую ситуацию
    public static function getState($f3) {
        // Запрос информации о пользователе
        if ($f3->exists('SESSION.user_id')) {
            $rows=$user_info=($f3->get('db')->exec(
                'select * from users where id=:id',
                array(':id'=>$f3->get('SESSION.user_id'))));
            $user_info=$rows[0];
        }
        // Создание барабана для розыгрыша, если структура пустая
        if (!$f3->exists('SESSION.baraban')) $f3->set('SESSION.baraban',Play::buildBaraban($f3));
        // Поиск предметов, по которым необходимо решение (выдаются по одному)
        $rows=$f3->get('db')->exec(
            'select d.id,i.id as id_item,i.name,i.cost from delivery as d inner join items as i on d.id_item=i.id where d.fl_accept=0 and d.id_user=:id',
            array(':id'=>$f3->get('SESSION.user_id')));
        if (count($rows)>0) $item_win=$rows[0];
        // Вывод структуры состояния игры
        response::json(array(
            'user_id'=>(int)($f3->get('SESSION.user_id')),  // ID пользователя, если не авторизован -- будет 0
            'user_name'=>(isset($user_info['login'])?$user_info['login']:'??'),
            'money_win'=>(isset($user_info['money_win'])?$user_info['money_win']:0),
            'money_win_txt'=>(isset($user_info['money_win'])?
                'У вас есть $'.$user_info['money_win'].', вы можете вывести их на карту, либо поменять на '.round($user_info['money_win']*self::getParam($f3,'money2bonus')).' бонусов.' :''),
            'item_win'=>(isset($item_win)?$item_win:array()),
            'item_win_txt'=>(isset($item_win)?
                'У вас есть '.$item_win['name'].', вы можете получить его по почте, либо поменять на $'.round($item_win['cost']*self::getParam($f3,'item2money')).'.':''),
            'bonus'=>(isset($user_info['bonus'])?$user_info['bonus']:0),
            'baraban'=>$f3->get('SESSION.baraban')
        ));
    }

    // Получение приза, возвращает номер выигранного лота и текстовое сообщение
    public static function getPrize($f3) {
        if ($f3->exists('SESSION.user_id') && $f3->get('SESSION.user_id')>0) {
            // Запрос настроек
            $options=self::buildGameOptions($f3);
            // Создание барабана, если его нет
            if (!$f3->exists('SESSION.baraban')) $baraban=Play::buildBaraban($f3);
            else $baraban=$f3->get('SESSION.baraban');
            // Выбор случайного лота
            do {
                $rnd_prize=rand(1,$options['baraban_count']);
                $prize=$baraban[$rnd_prize];
            } while (!self::checkBaraban($prize,$options)); // проверка того, что данный лот можно выиграть
            $message='';
            if ($prize['typ']=='bonus') {
                // Выигран бонус
                $f3->get('db')->exec(
                    'update users set bonus=bonus+:bonus',
                    array(':bonus'=>$prize['amount']));
                $message='Вы выиграли '.$prize['amount'].' бонусов!';
            } else if ($prize['typ']=='money') {
                // Выиграны деньги
                $f3->get('db')->exec(
                    'update users set money_win=money_win+:money',
                    array(':money'=>$prize['amount']));
                $f3->get('db')->exec(
                    'update params set value=value-:money where name="money"',
                    array(':money'=>$prize['amount']));
                $message='Вы выиграли $'.$prize['amount'].'!';
            } else if ($prize['typ']=='item') {
                // Выигран предмет
                $f3->get('db')->exec(
                    'update items set amount=amount-1 where id=:id_item',
                    array(':id_item'=>$prize['item']['id']));
                $f3->get('db')->exec(
                    'insert into delivery(id_item,id_user) values (:id_item,:id_user)',
                    array(':id_item'=>$prize['item']['id'],':id_user'=>$f3->get('SESSION.user_id')));
                $message = 'Вы выиграли $' . $prize['item']['name'] . '!';
            }
            response::json(array('win_message'=>$message, 'lot_id'=>$rnd_prize));
            $f3->clear('SESSION.baraban');
        } else response::error('Авторизуйтесь, пожалуйста.');
    }

    // Создание структуры для проверки возможности выигрыша и для подбора позиций для барабана
    public static function buildGameOptions($f3) {
        // Опции по умолчанию, если в БД не будет нужных полей. Комментарии к этим параметрам есть в docs/tz.pdf
        $options=array(
            'baraban_count'=>10, // количество слотов с вариантами выигрыша, при изменении это число надо продублировать в play.js
            'money'=>0,
            'money2bonus'=>1,
            'item2money'=>1,
            'money_min'=>1,
            'money_max'=>10,
            'bonus_min'=>1,
            'bonus_max'=>10,
            'chance_bonus'=>5,
            'chance_money'=>5,
            'chance_item'=>5
        );
        // Запрос настроек из БД
        $rows=$f3->get('db')->exec('select name,value from params');
        foreach($rows as $row) $options[$row['name']]=$row['value']; // Перезаписываем значения по-умолчанию теми, что из БД
        // Запрос наличия предметов (их мало, загрузим все, но можно лимитировать и случайно сортировать через sql)
        $rows=$f3->get('db')->exec('select id,name,amount,cost from items where amount>0');
        $options['item_count']=count($rows); // Количество предметов
        $cc=0;
        $chance=0;
        foreach($rows as $row) {
            $row['real_chance']=$chance;
            $chance+=$row['amount'];
            $options['items'][++$cc]=$row; // Засовываем в массив
        }
        // При подборе предметов будем учитывать их количество. Чем больше количество конкретного предмета, тем выше вероятность его попадания на барабан.
        // Чуть выше мы сохранили в запись каждого предмета количество предметов до него нарастающим итогом. А теперь финальное число запишем сюда,
        // чтобы потом брать случайное число и смотреть в какой предмет оно попало. По идее, тут можно учесть цену предмета.
        $options['last_item_chance']=$chance;
        // Аналогично распределим вероятности между бонусами, деньгами и вещами, чтобы выбирать их случайным числом
        $options['chance_bonus_to']=$options['chance_bonus'];
        $options['chance_money_to']=$options['chance_bonus']+$options['chance_money'];
        $options['chance_item_to']=$options['chance_bonus']+$options['chance_money']+$options['chance_item'];
        return $options;
    }

    // Создание нового барабана для розыгрыша
    public static function buildBaraban($f3) {
        $baraban=array();
        $options=self::buildGameOptions($f3);
        for ($q=1;$q<=$options['baraban_count'];$q++) {
            do {
                if ($q==1) $rnd_typ=0; // на всякий случай первый элемент барабана делаем на бонусы (если в казино кончатся деньги и предметы)
                else $rnd_typ=rand(0,$options['chance_item_to']);  // "вес" разных выигрышей сложен так: бонусы->деньги->вещи
                if ($rnd_typ>$options['chance_money_to'] && $options['item_count']>0) {
                    $rnd_item=rand(0,$options['last_item_chance']);
                    $item_id=0;
                    for ($w=1;$w<=$options['item_count'];$w++) if ($options['items'][$w]['real_chance']<=$rnd_item) $item_id=$w;
                    $baraban[$q]=array(
                        'typ'=>'item', // Кладем на барабан предмет
                        'item'=>array(
                            'id'=>$options['items'][$item_id]['id'],
                            'name'=>$options['items'][$item_id]['name']
                        )
                    );
                } elseif ($rnd_typ>$options['chance_bonus_to']) {
                    $baraban[$q]=array(
                        'typ'=>'money', // Кладем на барабан деньги
                        'amount'=>rand($options['money_min'],$options['money_max'])
                    );
                } else {
                    $baraban[$q]=array(
                        'typ'=>'bonus', // Кладем на барабан бонусы
                        'amount'=>rand($options['bonus_min'],$options['bonus_max'])
                    );
                }
            } while (!self::checkBaraban($baraban[$q],$options)); // проверка того, что данный лот можно выиграть
        }
        return $baraban;
    }

    // Проверка возможности выигрыша конкретной позиции
    public static function checkBaraban($item,&$options) {
        $fl=0;
        if ($item['typ']=='item') {
            foreach ($options['items'] as $value) if ($value['id']==$item['item']['id']) $fl=1; // Проверяем что данный предмет есть в списке
        }
        return (
            ($item['typ']=='bonus') ||   // Бонусы всегда проходят
            ($item['typ']=='money' && $item['amount']<=$options['money']) || // Деньги проходят, если их не больше, чем есть у казино
            ($item['typ']=='item' && $fl>0 && $item['item']['cost']*$options['item2money']<=$options['money'])); // Предмет можно оплатить и он есть в наличии
    }


}

?>