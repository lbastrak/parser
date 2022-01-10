# parser


Parsing it's easy!


```php

require_once __DIR__ . '/parser/parser.php';

$curl = new CURL('www.example.com');
$curl->proxy = true;

$curl->set_post(http_build_query([
	"it's easy" => true
]));

$html = $curl->exec($close = true, $http_code);

if($http_code == 200) {
	
	$doc = phpQuery::newDocumentHTML($html);
	$doc->find('script,noscript')->remove();
	echo $doc;
}

// Debug response cookies

dd($curl->LAST_COOKIES);

```

Multi curl example


```php

$mc = new CURL('');
$channels = [];

foreach ($url_list as $url) {

	$curl = new CURL($url);


	$channels[] = [
		'ch' => $curl->ch,
		'url' => $url
	];
	$mc->multi_add($curl->ch);
}


$mc->multi_exec();

foreach($channels as $ch) {

	$html = $mc->multi_content($ch['ch']);
}

```