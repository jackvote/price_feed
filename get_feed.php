<?php
// Формирование price-feed для GOLOS относительно BTS из альтернативных источников

// cryptocharts.ru перекрыл доступ ботам в очередной раз. Меняю ресурс для получеиня курса
$url="https://coincodex.com/convert/bitshares/rub/"; // URL, к которому вы отправляете запрос
$ch = curl_init($url); // Инициализируем cURL-сессию

// Установка параметров запроса
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Указываем, что хотим получить результат запроса возвращаемый в качестве возвращаемого значения функции curl_exec
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3'); // Устанавливаем заголовок User-Agent для имитации браузера
// Добавление других параметров запроса...

$p = curl_exec($ch); // Выполняем запрос
curl_close($ch); // Закрываем cURL-сессию

// Обрабатываем полученный результат
//$p=iconv("utf-8", "windows-1251", $p); // выбор библиотеки перекодировки
//$p=mb_convert_encoding($p, "windows-1251", "utf-8"); // исходники уже в UTF, осталяю строку, если опять прийдётся менять url

// Поиск соответствия
$regex = '/BTS to (\d*\.?\d+)\s*RUB./';
preg_match($regex, $p, $matches);
$bts = $matches[1];

$p=file_get_contents("https://ticker.rudex.org/api/v1/ticker"); // курс GOLOS-BTS
$obj=json_decode($p);

$time=time();
$count=0;

while (true) { // торги по золоту выставляются не за каждый день (выходные и др.) поэтому берём за последнюю имеющуюся дату
  $d=date("d/m/Y", $time);
  $req="http://www.cbr.ru/scripts/xml_metall.asp?date_req1=".$d."&date_req2=".$d;
//  http://www.cbr.ru/scripts/xml_metall.asp?date_req1=01/07/2001&date_req2=13/07/2001
//  $req="https://cbr.ru/hd_base/metall/metall_base_new/?UniDbQuery.Posted=True&UniDbQuery.From=".$d."&UniDbQuery.To=".$d."&UniDbQuery.Gold=true&UniDbQuery.so=1";
  $p=file_get_contents($req); // курс GOLD-RUB
  $count++;
  $t=explode("<Sell>", $p);
  $t=explode("</Sell>", $t[1]);
  if (isset($t) && $t[0]<>0 || $count>14) {
      break;
  } else {
      $time=$time-24*60*60;
  }
}

//var_dump($obj->GLS_BTS->last_price);
$golos=$bts * (float)$obj->GLS_BTS->last_price; // стоимость GOLOS в битшарах умножаем на курс битшар к рублю - получаем стоимость GOLOS в рублях
$gold=(float)str_replace(",", ".", $t[0])/1000; // стоимость милиграмма золота в рублях

$feed=round($golos/$gold, 3); // соотношение GOLOS/GBG

$obj='{"GOLOS":'.$golos.', "GOLD":'.$gold.', "DATEG":"'.$d.'", "FEED":'.$feed.'}';

echo $obj;
// (с) https://github.com/jackvote
?>
