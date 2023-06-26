# Parsing it's easy!

Easy solution for creating bots and parsers


```php

require_once __DIR__ . '/parser/bootstrap.php';

$curl = new CURL('www.example.com');
$curl->proxy = true;

$curl->set_post(http_build_query([
	"parsing" => "it's easy"
]));

$html = $curl->exec($close = true, $curl_info);

if($curl_info['http_code'] == 200) {
	
	$doc = phpQuery::newDocumentHTML($html);
	$doc->find('script,noscript')->remove();
	echo $doc;
}

// Debug response cookies

dd($curl->LAST_COOKIES);

// Debug response headers

dd($curl->LAST_HEADER);

```

Multi curl example


```php

$mc = new CURL;

$url_list = [
	[
		'url' => 'www.example.com?page=1'
	],
	[
		'url' => 'www.example.com?page=2'
	],
];

foreach ($url_list as $list_key => $item) {

	$curl = new CURL($item['url']);

	$mc->multi_add($curl->ch, $list_key);
}

$mc->multi_exec(function($content, $list_key, $info, $cookies, $headers) use (&$url_list) {
	
	$url_list[$list_key]['http_code'] = $info['http_code'];
	if($info['http_code'] == 200) {
		
		$doc = phpQuery::newDocumentHTML($content);
		$url_list[$list_key]['title'] = $doc->find('h1')->text();
	}
});

dd($url_list); // Debug parsing result


```