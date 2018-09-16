const baraban_count=10; // Количество слотов для розыгрыша, должно соответствовать числу в Play::buildGameOptions
let speed=1;
let dx=0;
let target_id=0;
let stage=0;

// Действия при загрузке страницы
$(function() {
    getStats(); // Запрос состояния
    setInterval(scroller,25);
});

// Парсинг текущего состояния, генерируется в Play::getState
function parseStats(data) {
    if (data.message) { // Приехало сообщение об ошибке
        $('#message_txt').html(data.message);
        $('#container_message').show();
    } else { // нет ошибок
        $('#container_message').hide();
    }
    if (data.user_id) { // Если в состоянии есть user_id то ..
        $('#container_login').hide(); // .. скрыть форму авторизации
        $('#container_user').show(); // .. показать информацию о пользователе
        $('#user_name').html(data.user_name);
        $('#user_bonus').html(data.bonus);
    } else {            // Если в состоянии нет user_id то ..
        $('#container_login').show(); // .. показать форму авторизации
        $('#container_user').hide(); // .. скрыть информацию о пользователе
    }
    // Барабан
    for (let q=1;q<=baraban_count;q++) {
        let fone='';
        let text='';
        if (data.baraban[q].typ=='bonus') { fone='bonus'; text='<b>'+data.baraban[q].amount+'</b> шт.';}
        if (data.baraban[q].typ=='money') { fone='money'; text='$<b>'+data.baraban[q].amount+'</b>';}
        if (data.baraban[q].typ=='item') { fone='item_'+data.baraban[q].item.id; text='<b>'+data.baraban[q].item.name+'</b>'; }
        $('#card_'+(q-1).toString()).css('background-image','url(ui/images/'+fone+'.png)');
        $('#card_text_'+(q-1).toString()).html(text);
    }
    // Если есть предметы на конвертацию
    if (data.item_win && data.item_win.id) {
        $('#id_item').val(data.item_win.id);
        $('#card_B').css('background-image','url(ui/images/item_'+data.item_win.id_item+'.png)');
        $('#card_text_B').html(data.item_win.name);
        $('#text_B').html(data.item_win_txt);
        $('#container_item2money').show();
    } else {
        $('#container_item2money').hide();
    }
    // Если есть деньги на конвертацию
    if (data.money_win && data.money_win>0) {
        $('#card_A').css('background-image','url(ui/images/money.png)');
        $('#card_text_A').html(data.money_win);
        $('#text_A').html(data.money_win_txt);
        $('#container_money2bonus').show();
    } else {
        $('#container_money2bonus').hide();
    }
    // Сброс вращения лотов
    stage=0;
    speed=1;
    target_id=0;
}

// Отправка формы авторизации
function sendLoginForm() {
    $.ajax({
        type: "POST",
        url: '/api/login',
        data: { 'login': $('#login').val(), 'pass': $('#pass').val() },
        success: parseStats
    });
}

// Выход
function sendLogoutForm() {
    if (confirm("Точно выйти?")) {
        $.ajax({
            type: "POST",
            url: '/api/logout',
            success: parseStats
        });
    }
}

// Реакция на крестик рядом с сообщением
function closeMessage() {
    $('#container_message').hide();
}

// Запрос текущего состояния
function getStats() {
    $.ajax({
        type: "GET",
        url: '/api/get_state',
        success: parseStats
    });
}

// Вращение барабана
function scroller() {
    if (target_id>0) { // Движение барабана к выигранному слоту
        if (stage==0) { // Нулевая стадия -- разгон
            if (speed<20) speed+=0.5;
            else stage=1;
        }
        if (stage==2) {
            if (speed>0) speed-=0.1; // Вторая стадия -- торможение
            else {
                stage=3; // Третья стадия -- остановились
                $('#container_win_message').show();
            }
        }
        if (stage==1 && (Math.abs(target_id*100-dx+10)<=20 || Math.abs((target_id-baraban_count)*100-dx+10)<=20)) { // Первая стадия -- точное прицеливание
            stage=2;
            dx=target_id*100-10; // Исправление координат для точной остановки
        }
    }
    // Смещение всех лотов по кругу
    dx=dx+speed;
    if (dx>baraban_count*100) dx=dx-baraban_count*100;
    for (let q=0;q<baraban_count;q++) {
        let xx=dx+q*100+202;
        while (xx>baraban_count*100) xx-=baraban_count*100;
        if (xx>400) xx=400;
        $('#card_'+q.toString()).css('left',xx);
    }
}

// Запрос целевой позиции
function getPrize() {
    if (stage==0) { // Если сейчас нет раскрутки
        $.ajax({
            type: "GET",
            url: '/api/get_prize',
            success: rollTo
        });
    }
}

// Установка целевой позиции
function rollTo(data) {
    if (data.message) {
        $('#message_txt').html(data.message);
        $('#container_message').show();
    } else if (data.lot_id) {
        target_id=baraban_count-data.lot_id+1;
        $('#win_message_txt').html(data.win_message);
    }
}

// Закрытие сообщения о выигрыше
function refreshGame() {
    $('#container_win_message').hide();
    getStats();
}

// Конвертация денег в бонусы
function moveMoney2bonus() {
    $.ajax({
        type: "POST",
        url: '/api/money2bonus',
        success: parseStats
    });
}

// Пометить деньги на вывод
function moveMoney2card() {
    $.ajax({
        type: "POST",
        url: '/api/money2card',
        success: parseStats
    });
}

function moveItem2money() {
    $.ajax({
        type: "POST",
        url: '/api/item2money',
        data: { 'id': $('#id_item').val() },
        success: parseStats
    });
}

function moveItem2post() {
    $.ajax({
        type: "POST",
        url: '/api/item2post',
        data: { 'id': $('#id_item').val() },
        success: parseStats
    });
}