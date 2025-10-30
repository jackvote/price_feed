<?php
// Формирование price-feed для GOLOS относительно USDT из альтернативных источников

// Part I: Получение курса USDT-RUB с CoinGecko API
$url = "https://api.coingecko.com/api/v3/simple/price?ids=tether&vs_currencies=rub"; // API-запрос для курса USDT/RUB
$p = file_get_contents($url); // Загружаем JSON с API
if (!$p) {
    die("Ошибка: Не удалось загрузить данные с CoinGecko API.\n");
}

$data = json_decode($p, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($data['tether']['rub'])) {
    die("Ошибка: Не удалось декодировать JSON API или найти курс USDT/RUB.\n");
}

$usdt = (float) $data['tether']['rub']; // Стоимость 1 USDT в RUB
// echo "Курс USDT/RUB: $usdt\n"; // Лог для отладки

// Part II: Получение курса GOLOS/USDT и GOLD/RUB
$p = file_get_contents("https://ticker.rudex.org/api/v1/ticker"); // Курс GOLOS/USDT
if (!$p) {
    die("Ошибка: Не удалось загрузить ticker Rudex.\n");
}
$obj = json_decode($p);
if (!$obj || !isset($obj->GLS_USDT->last_price)) {
    die("Ошибка: Не удалось декодировать JSON Rudex или найти GLS_USDT.\n");
}

$time = time();
$count = 0;

while (true) { // Торги по золоту не каждый день, берём последнюю доступную дату
    $d = date("d/m/Y", $time);
    $req = "http://www.cbr.ru/scripts/xml_metall.asp?date_req1=" . $d . "&date_req2=" . $d; // Запрос к ЦБ РФ
    $p = file_get_contents($req); // Курс GOLD/RUB
    if (!$p) {
        $time = $time - 24 * 60 * 60; // Минус день
        $count++;
        if ($count > 14) {
            die("Ошибка: Не удалось найти курс золота за последние 14 дней.\n");
        }
        continue;
    }
    $count++;
    $t = explode("<Sell>", $p);
    $t = explode("</Sell>", $t[1]);
    if (isset($t[0]) && $t[0] != 0 && $t[0] !== '') {
        break;
    } elseif ($count > 14) {
        die("Ошибка: Не удалось найти курс золота за последние 14 дней.\n");
    } else {
        $time = $time - 24 * 60 * 60; // Минус день
    }
}

$golos = $usdt * (float) $obj->GLS_USDT->last_price; // Стоимость GOLOS в USDT умножаем на курс USDT к RUB - получаем стоимость GOLOS в RUB
$gold = (float) str_replace(",", ".", $t[0]) / 1000; // Стоимость миллиграмма золота в RUB

$feed_raw = round($golos / $gold, 3); // Соотношение GOLOS/GBG округляем
$feed = number_format($feed_raw, 3, '.', ''); // Форматируем до 3 знаков после запятой как строку ("1.234" или "1.000")

// Вывод JSON с добавлением USDT
$obj = '{"GOLOS":' . $golos . ', "USDT":' . $usdt . ', "GOLD":' . $gold . ', "DATEG":"' . $d . '", "FEED":' . $feed . '}';

echo $obj;
// (с) https://github.com/jackvote
?>
