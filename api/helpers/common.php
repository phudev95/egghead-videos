<?php
	// Local - production
	if (in_array($_SERVER["REMOTE_ADDR"], array (
		"127.0.0.1",
		"::1"
	))) {
		define('FOLDER', '/egghead_videos/');
	} else {
		define('FOLDER', '/egghead_videos/');
	}

	// Define
	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
		define('PATH_URL', 'https://' . $_SERVER['HTTP_HOST'] . FOLDER);
	} else {
		define('PATH_URL', 'http://' . $_SERVER['HTTP_HOST'] . FOLDER);
	}
	define('PATH_STREAM_DOWNLOAD', PATH_URL . 'api/download.php');

	if (!function_exists('parse_post_data')) {
		function parse_post_data () {
			$result = array ();
			$post_data = file_get_contents("php://input");
			if (!empty($post_data)) {
				$result = json_decode($post_data, 1);
			} else if (!empty($_POST)) {
				$result = $_POST;
			}
			return $result;
		}
	}

	if (!function_exists('response_json')) {
		function response_json ($output = array ()) {
			if (!headers_sent()) {
				header('Content-Type: application/json; charset=utf-8');
			}
			echo json_encode($output);
			die();
		}
	}

	if (!function_exists('browser_request')) {
		function browser_request ($is_curl = true) {
			$browser = new ChipVN_Http_Client();
			$browser->useCurl($is_curl);
			$browser->setUserAgent(get_user_agent());
			$browser->setTimeout(20);
			if (defined('REQUEST_SOCK') && REQUEST_SOCK) {
				$browser->setProxy(REQUEST_SOCK);
			}
			return $browser;
		}
	}

	if (!function_exists('log_data')) {
		function log_data ($data) {
			echo "<textarea rows='20' cols='150'>" . print_r($data, 1) . "</textarea>";
			die();
		}
	}

	if (!function_exists('parse_title')) {
		function parse_title ($str = '') {
			$result = array (
				'title' => $str,
				'category' => 'Undefined'
			);

			preg_match("/^([^-]+)-(.*)$/is", $str, $matches);
			if (count($matches) === 3) {
				$result['title'] = trim($matches[2]);
				$result['category'] = trim($matches[1]);
			}

			return $result;
		}
	}

	if (!function_exists('clean_query_utm')) {
		/**
		 * Clean query parameters on URL like: ?utm_source=blabla&utm_medium=blabla&utm_campaign=blabla
		 * @param string $url
		 * @return mixed
		 */
		function clean_query_utm ($url = ''){
			return preg_replace('/[?]utm_source=.*/','', $url);
	    }
	}

	if (!function_exists('formatBytes')) {
		function formatBytes ($size, $precision = 2) {
			$result = 0;

			if (is_numeric($size) && $size > 0) {
				$base = log($size, 1024);
				$suffixes = array (
					'',
					'K',
					'M',
					'G',
					'T'
				);
				$result = round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
			}

			return $result;
		}
	}

	if (!function_exists('throwRequestError')) {
		function throwRequestError ($browser) {
			if (!empty($browser->errors)) {
				throw new Exception(sprintf('%s', implode(', ', $browser->errors)));
			}
		}
	}
	function get_user_agent () {
		$agents = array (
			"Mozilla/5.0 (iPhone; CPU iPhone OS 6_1_3 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10B329 Safari/8536.25",
			"Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) CriOS/30.0.1599.16 Mobile/11A465 Safari/8536.25 (2637345E-FAD0-4B3B-A7E9-3FB6E057CFDD)",
			"Mozilla/5.0 (iPhone; CPU iPhone OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A405 Safari/8536.25",
			"Mozilla/5.0 (iPhone; CPU iPhone OS 7_0_2 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A501 Safari/9537.53",
			"Mozilla/5.0 (iPhone; CPU iPhone OS 6_1_3 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Mobile/10B329",
			"Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Mobile/11A465",
			"Mozilla/5.0 (iPhone; CPU iPhone OS 6_1 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10B144 Safari/8536.25",
			"Mozilla/5.0 (iPhone; CPU iPhone OS 5_1_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9B206 Safari/7534.48.3",
			"Mozilla/5.0 (iPhone; CPU iPhone OS 7_0_2 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) CriOS/30.0.1599.12 Mobile/11A501 Safari/8536.25",
			"Mozilla/5.0 (iPhone; CPU iPhone OS 7_0_2 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Mobile/11A501",
			"Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5"
		);
		return random_elements($agents);
	}

	if (!function_exists('random_elements')) {
		function random_elements ($elements = array ()) {
			if (empty($elements))
				throw new Exception("Data is empty");
			return $elements[array_rand($elements)];
		}
	}

	if (!function_exists('html_entity')) {
		function html_entity ($string, $quote_style = ENT_COMPAT, $charset = "utf-8") {
			$string = html_entity_decode($string, $quote_style, $charset);
			$string = preg_replace_callback('~&#x([0-9a-fA-F]+);~i', "chr_utf8_callback", $string);
			$string = preg_replace('~&#([0-9]+);~e', 'chr_utf8("\\1")', $string);
			return $string;
		}
	}

	if (!function_exists('fetch_value')) {
		function fetch_value ($str, $find_start = '', $find_end = '') {
			if ($find_start == '') {
				return '';
			}
			$start = strpos($str, $find_start);
			if ($start === false) {
				return '';
			}
			$length = strlen($find_start);
			$substr = substr($str, $start + $length);
			if ($find_end == '') {
				return $substr;
			}
			$end = strpos($substr, $find_end);
			if ($end === false) {
				return $substr;
			}
			return substr($substr, 0, $end);
		}
	}

	if (!function_exists('chr_utf8_callback')) {
		function chr_utf8_callback ($matches) {
			return chr_utf8(hexdec($matches[1]));
		}
	}

	if (!function_exists('chr_utf8')) {
		function chr_utf8 ($num) {
			if ($num < 128)
				return chr($num);
			if ($num < 2048)
				return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
			if ($num < 65536)
				return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
			if ($num < 2097152)
				return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
			return '';
		}
	}

	if (!function_exists('cut_str')) {
		function cut_str ($str, $left, $right) {
			$str = substr(stristr($str, $left), strlen($left));
			$leftLen = strlen(stristr($str, $right));
			$leftLen = $leftLen ? -($leftLen) : strlen($str);
			$str = substr($str, 0, $leftLen);
			return $str;
		}
	}

	if (!function_exists('get_stream_link')) {
		function get_stream_link ($no = 0, $name = '', $mime_type = '', $source = '') {
			$order = $no < 10 ? "0{$no}" : $no;
			$name = sprintf('%s. %s%s', $order, $name, get_ext($mime_type));
			return sprintf('%1$s?src=%2$s&name=%3$s', PATH_STREAM_DOWNLOAD, urlencode($source), urlencode($name));
		}
	}

	if (!function_exists('get_ext')) {
		function get_ext ($mime_type = '') {
			$mime_types = include 'mime.php';
			return array_search($mime_type, $mime_types);
		}
	}
?>