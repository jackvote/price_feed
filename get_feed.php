<?php

// Формирование price-feed для GOLOS относительно BTS

$p=file_get_contents("https://www.calc.ru/kurs-BTS-RUB.html"); // курс BTS-RUB

$t=explode("(RUB)</b><br><b>", $p);

$t=explode("(BTS)</b>", $t[0]);

$h=preg_match("/.+(\d+\.\d+).+/i", $t[1], $k);

$p=file_get_contents("https://ticker.rudex.org/api/v1/ticker"); // курс GOLOS-BTS

$obj=json_decode($p);

$time=time();
$countT=0;
while (true) {
$d=date("d/m/Y", $time);
$req="http://www.cbr.ru/scripts/xml_metall.asp?date_req1=".$d."&date_req2=".$d;

$p=file_get_contents($req); // курс GOLD-RUB
$count++;
$t=explode("<Sell>", $p);
$t=explode("</Sell>", $t[1]);
  if (isset($t) && $t[0]<>0 || $count>7) {
      break;
  } else {
      $time=$time-24*60*60;
  }
}

$golos=$k[1]*$obj->GLS_BTS->last_price; // стоимость GOLOS в битшарах умножаем на курс битшар к рублю - получаем стоимость GOLOS в рублях
$gold=(float)str_replace(",", ".", $t[0])/1000; // стоимость милиграмма золоота в рублях
$koef=round($golos/$gold, 3); // соотношение GOLOS/GBG

$obj='{"GOLOS":'.$golos.', "GOLD":'.$gold.', "DATEG":'.$d.', "FEED":'.$koef.'}';

echo $obj;

?>
