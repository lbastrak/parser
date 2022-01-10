<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
//ini_set("error_log", __DIR__ . '/errors.txt');

function dd($str) {
	echo '<br><pre style="background:#ffe4e4;display:inline-block;margin:5px;float:left;padding:20px;border-radius:5px;">';
	var_dump($str);
	echo '</pre><br>';
}

function create_token($length = 50) {
	return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $length)), 0, $length);
}

function translit($str)
{

    $tr = array(
        "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
        "Д"=>"D","Е"=>"E","Ж"=>"J","З"=>"Z","И"=>"I",
        "Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
        "О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
        "У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH",
        "Ш"=>"SH","Щ"=>"SCH","Ъ"=>"","Ы"=>"YI","Ь"=>"",
        "Э"=>"E","Ю"=>"YU","Я"=>"YA","а"=>"a","б"=>"b",
        "в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
        "з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
        "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
        "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
        "ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
        "ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya",
    "."=>"_"," "=>"-","?"=>"_","/"=>"_","\\"=>"_",
    "*"=>"_",":"=>"_","*"=>"_","\""=>"_","<"=>"_",
    ">"=>"_","|"=>"_"
    );
    return strtr($str,$tr);
}


function build_sorter($key) {
    return function ($a, $b) use ($key) {
        return strnatcmp($a[$key], $b[$key]);
    };
}

function http_build_cookie(array $var) {
	return urldecode(preg_replace('/&/u', '; ', http_build_query($var)).';');
}

function get_proxy_list($URL = 'https://hidemy.name/ru/proxy-list/?__cf_chl_jschl_tk__=358338e812e5404a89ba07580be73cd05c459cfc-1593124805-0-AUb38_swKrDRexdeYg8XeFNP8HQPHx-SBtNAKD1K_TgU57MYLPCb_y3kawRrkPGpSResZy6OavY5rz8xpQnEDb3ElYUq8IQG1VlAQ6NfH_DKn2FpGqKUXTp_AT4WBXk-KVz0zCGu5iTJwgyNLO4kCZxrtd30lGPIydzAUSjZZ8zGrYidVTtEU0osx-__6ty2Rsub-Tv06sNwoPMQKMjU0ajF7yfaKRnytGZZGemnDQNVOhiBVke7kRYbVJHmcAW8ybTTFCGan7l5aMy4w41zOndGAFIv4r8uP0PHJFRfu7pB') {
	// &type=4   			<--- only SOCKS 4
	$proxy_types = [
		'HTTP' => 0,
		'HTTPS' => 2,
		'SOCKS4' => 4,
		'SOCKS4A' => 6,
		'SOCKS5' => 5,
	];
	$curl = new Curl($URL);
	$list = [];
	foreach (phpQuery::newDocumentHTML($curl->exec())->find('.table_block tbody tr') as $tr) {
		$tr = pq($tr);
		preg_match('/^[A-z0-9]+/',$tr->find('td')->eq(4)->text(),$matches);
		$list[] = [
			'host' => $tr->find('td')->eq(0)->text() . ':' . $tr->find('td')->eq(1)->text(),
			'type' => $tr->find('td')->eq(4)->text(),
			'protocol' => $matches[0],
			'speed' => (int) preg_replace('/\D/', '', $tr->find('p')->text()),
			'curlproxytype' => $proxy_types[ $matches[0] ],
			'forl' => $matches[0] . "://" . $tr->find('td')->eq(0)->text() . ':' . $tr->find('td')->eq(1)->text()
		];
	}
	usort($list, build_sorter('speed'));
	//shuffle($list);
	return $list;
}

?>



