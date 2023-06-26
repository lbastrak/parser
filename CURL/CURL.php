<?php
/**
 * @version 4.0
 * @link https://t.me/runphp
 * @link https://github.com/lbastrak
 * @author Arkady Paramazyan <run.php@mail.ru>
 * @package Parser
 */

class CURL {

	public $ch;
	public $url;

	// PROXY SETTINGS
	const PROXY_RECEVING_LIMIT = 10*60; // 10 mints
	
	public $proxylist_url = 'https://hidemy.name/ru/proxy-list/';
	public $proxy = false;
	public $shuffle_proxy = false;

	//
	public $_cookieFileLocation = '';
	public $referer = "";
	private $referer_links = __DIR__ . '/referer_links.txt';
	public $useragent = "";
	private $user_agents = __DIR__ . '/user_agents.txt';
	public $reset_cookie = false;
	public $LAST_HEADERS = [];
	public $LAST_COOKIES = [];

	//Multicurl
	public $multi;
	public $multi_channels = [];

	function __construct( $target_url = '', $use_cookie = true) {
		
		if($target_url == '')
			return true;

		$this->url = $target_url;
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_URL, $this->url);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		//curl_setopt($this->ch, CURLOPT_VERBOSE, 1);
		curl_setopt($this->ch, CURLOPT_HEADER, true);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 0);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 29);

		$this->set_random_user();
		ob_start();
		debug_print_backtrace();
		$backtrace = ob_get_clean();

		preg_match('/[^\s]+\.php/', $backtrace, $matches);

		$this->_cookieFileLocation =  dirname($matches[0]) . '/cookies.txt';
		
		if($use_cookie) {
			$this->set_cookie_path($this->_cookieFileLocation);
		}
 	}

 	public function time_limit($time) {
 		
 		if($this->url != '')
 			curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $time-3);
 		ini_set('max_execution_time', $time);
		set_time_limit($time);
		return true;
 	}

 	public function set_random_user() {
 		//$languages = ['ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7', 'ru-RU,ru;q=0.9,en-us,en;q=0.5'];
 		$languages = ['en-US,en;q=0.9,en-US;q=0.8,en;q=0.7', 'en-US,en;q=0.9,en-us,en;q=0.5'];
 		
 		$header[] = "Accept-Language: " . $languages[array_rand($languages)];
 		$header[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9";
 		//$header[] = "Accept-Encoding: gzip, deflate, br";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Sec-Fetch-Dest: document";
		$header[] = "Sec-Fetch-Mode: navigate";
		$header[] = "Sec-Fetch-Site: same-origin";
		$header[] = "Sec-Fetch-User: ?1";
		$header[] = "Upgrade-Insecure-Requests: 1";

 		$referers = preg_split('/\n/', file_get_contents($this->referer_links));
		$this->referer = trim($referers[ array_rand($referers) ] );

		$user_agents = preg_split('/\n/', file_get_contents($this->user_agents));
		$this->useragent = stripcslashes( trim($user_agents[ array_rand($user_agents) ]) );
		
		curl_setopt($this->ch, CURLOPT_REFERER, trim((string) $this->referer));
		curl_setopt($this->ch, CURLOPT_USERAGENT, (string) $this->useragent);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);
 	}

 	public function set_proxy($proxy, $type = -1) {
		
		curl_setopt($this->ch,CURLOPT_PROXY, $proxy);
		if($type != -1)
			curl_setopt($this->ch,CURLOPT_PROXYTYPE, $type);
		return true;
 	}

 	public function set_cookie_path( $path ) {
 		$this->_cookieFileLocation = $path;
 		curl_setopt($this->ch,CURLOPT_COOKIEJAR, $this->_cookieFileLocation);
        curl_setopt($this->ch,CURLOPT_COOKIEFILE, $this->_cookieFileLocation);
 		return true;
 	}

 	public function set_opt($option, $value) {
 		return curl_setopt($this->ch, $option, $value);
 	}

 	public function set_post($post) {
 		curl_setopt( $this->ch, CURLOPT_POST, 1);
 		curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $post);
 		return true;
 	}

 	public function get_proxy_list() {
 		
 		$dat = [];
 		$SETTINGS_DIR = 'proxy_list.json';
		if(file_exists($SETTINGS_DIR))
		    $dat = json_decode(file_get_contents($SETTINGS_DIR), JSON_OBJECT_AS_ARRAY);
		
		$proxylist = [];
		if(!isset($dat['proxylist']) || !isset($dat['proxy_get_limit']) || $dat['proxy_get_limit'] <= time()) {
		    
		    $proxylist = get_proxy_list($this->proxylist_url);
		    if(!count($proxylist))
		        exit("ERROR GET PROXYLIST");
		    $dat['PROXY_LAST_REQUEST'] = date("H:i d-m-Y");
		    $dat['proxy_get_limit'] = time() + $this::PROXY_RECEVING_LIMIT;
		    $dat['proxylist'] = $proxylist;
		    file_put_contents($SETTINGS_DIR, json_encode($dat));
		}else {
			
			if($this->shuffle_proxy)
				shuffle($dat['proxylist']);
			$proxylist = $dat['proxylist'];
		}
		$__proxylist = array_column($proxylist, 'host');
		return $__proxylist;
 	}
	
	public function exec( $close_connect = true, &$info = [], &$proxy_list = [], &$set_get_proxy = "") {

		if($this->reset_cookie)
			file_put_contents($this->_cookieFileLocation, '');


		$response = "";
		if($this->proxy) {
			
			if($proxy_list == [])
				$proxy_list = $this->get_proxy_list();
			$proxy_list = array_values($proxy_list);
			$i = 0;
			curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);

			if($set_get_proxy != "") {

				curl_setopt($this->ch, CURLOPT_PROXY, $set_get_proxy);
				$response = curl_exec($this->ch);
			}else {
				
				while ( $proxy = $proxy_list[$i] ) {

					curl_setopt($this->ch, CURLOPT_PROXY, $proxy);
					if ($response = curl_exec($this->ch) ) {
						$set_get_proxy = $proxy;
						break;
					}
					unset($proxy_list[$i]);
					$i++;
				}
			}

		} else 
			$response = curl_exec($this->ch);
		
		$info  = curl_getinfo($this->ch);

        $processed = self::curl_response_processing($info,$response);
        $this->LAST_HEADERS = $processed['headers'];
        $this->LAST_COOKIES = $processed['cookies'];
        $content = $processed['content'];

		if($close_connect)
			curl_close( $this->ch);
		
		return $content;
	}

	private static function curl_response_processing($info, $content) {

		$headers = substr($content, 0, $info['header_size']);
        $cookies = [];

        // Parse cookies
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $matches);
		foreach($matches[1] as $item) {
		    parse_str($item, $cookie);
		    $cookies = array_merge($cookies, $cookie);
		}

		//Parse headers
		$headers = [];
		foreach (preg_split('/(\s+\n+)/', substr($content, 0, $info['header_size'])) as $key => $value) {
			
			$value = preg_split('/([:]+)/', $value);
			if(count($value) != 2) continue;
			$headers[trim($value[0])]=trim($value[1]);
		}

		$content = substr($content, $info['header_size'], strlen($content));
        return [
        	'headers' => $headers,
        	'content' => $content,
        	'cookies' => $cookies
        ];
	}

	public function download_image($save_path, $limitWidth = 4000, $limitHeight = 4000) {

		curl_setopt_array($this->ch, [ // Укажем настройки для cURL
		    CURLOPT_TIMEOUT => 60,// Укажем максимальное время работы cURL
		    CURLOPT_FOLLOWLOCATION => 1,// Разрешим следовать перенаправлениям
		    CURLOPT_RETURNTRANSFER => 1,// Разрешим результат писать в переменную	    
		    CURLOPT_NOPROGRESS => 0,// Включим индикатор загрузки данных
		    CURLOPT_BUFFERSIZE => 5500,// Укажем размер буфера
		    CURLOPT_PROGRESSFUNCTION => function ($ch, $dwnldSize, $dwnld, $upldSize, $upld) {// Напишем функцию для подсчёта скачанных данных // Подробнее: http://stackoverflow.com/a/17642638
		        if ($dwnld > 1024 * 1024 * 5) { // Когда будет скачано больше 5 Мбайт, cURL прервёт работу
		            return -1;
		        }
		    },		    
		    //CURLOPT_SSL_VERIFYPEER => 1,// Включим проверку сертификата (по умолчанию)		    
		    //CURLOPT_SSL_VERIFYHOST => 2,// Проверим имя сертификата и его совпадение с указанным хостом (по умолчанию)		    
		    CURLOPT_CAINFO => __DIR__ . '/cacert.pem',// Укажем сертификат проверки // Скачать: https://curl.haxx.se/docs/caextract.html
		]);

		$raw   = $this->exec(false);
		$info  = curl_getinfo($this->ch);
		$error = curl_errno($this->ch);
		curl_close($this->ch);

		// Проверим ошибки cURL и доступность файла
		if ($error === CURLE_OPERATION_TIMEDOUT) {
			echo('Превышен лимит ожидания.');
			return false;
		}
		if ($error === CURLE_ABORTED_BY_CALLBACK) {
			echo('Размер не должен превышать 5 Мбайт.');
			return false;
		}
		if ($info['http_code'] !== 200) {
			echo('Файл не доступен.');
			return false;
		}

		$fi = finfo_open(FILEINFO_MIME_TYPE); // Создадим ресурс FileInfo
		$mime = (string) finfo_buffer($fi, $raw); // Получим MIME-тип используя содержимое $raw
		finfo_close($fi);// Закроем ресурс FileInfo

		if (strpos($mime, 'image') === false) { // Проверим ключевое слово image (image/jpeg, image/png и т. д.)
			echo('Можно загружать только изображения.');
			return false;
		}
		$image = getimagesizefromstring($raw);// Возьмём данные изображения из его содержимого
		if ($image[1] > $limitHeight) { // Проверим нужные параметры
			echo('Высота изображения не должна превышать 3000 точек.');
			return false;
		}
		if ($image[0] > $limitWidth) {
			echo('Ширина изображения не должна превышать 3000 точек.');
			return false;
		}

		$extension = image_type_to_extension($image[2]);// Сгенерируем расширение файла на основе типа картинки		
		$format = str_replace('jpeg', 'jpg', $extension);// Сократим .jpeg до .jpg

		$save_path = $save_path . $format;
		if (!file_put_contents($save_path, $raw)) {// Сохраним картинку с новым именем и расширением в папку
		    echo('При сохранении изображения на диск произошла ошибка.');
			return false;
		}
		return $save_path;
	}


	// Multi

	public function multi_add() {
		
		$args = func_get_args();
		$this->multi_channels[] = $args;
		
		if(!$this->multi)
			$this->multi = curl_multi_init();

		curl_multi_add_handle($this->multi, $args[0]);
	}

	public function multi_exec($callback = NULL) {

		if(!count($this->multi_channels))
			return false;

		$active = null;
		do {
		    $mrc = curl_multi_exec($this->multi, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		 
		while ($active && $mrc == CURLM_OK) {
		    if (curl_multi_select($this->multi) == -1) {
		        continue;
		    }

		    do {
		        $mrc = curl_multi_exec($this->multi, $active);
		    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
		}

		if($callback != NULL) {

			foreach ($this->multi_channels as $params) {
				
				$response = $this->multi_content($params[0],false);
				$info = curl_getinfo($params[0]);
				curl_close($params[0]);

		        $processed = self::curl_response_processing($info,$response);
		        $headers = $processed['headers'];
		        $cookies = $processed['cookies'];
		        $content = $processed['content'];

		        $ch = $params[0];
		        $params[0] = $content;
		        $params[] = $info;
		        $params[] = $cookies;
		        $params[] = $headers;
		        $params[] = $ch;
				call_user_func_array($callback, $params);
			}
		}

		return $mrc;
	}

	public function multi_content($channel, $close = true) {
		
		$contnet = curl_multi_getcontent($channel);
		curl_multi_remove_handle($this->multi, $channel);
		if($close)
			curl_close($channel);
		return $contnet;
	}
	//
}
