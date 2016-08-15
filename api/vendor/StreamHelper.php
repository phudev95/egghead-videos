<?php
	class StreamHelper {
		protected $proxy = '';

		protected function uft8html2utf8 ($s) {
			if (!function_exists('uft8html2utf8_callback')) {
				function uft8html2utf8_callback ($t) {
					$dec = $t[1];
					if ($dec < 128) {
						$utf = chr($dec);
					} else if ($dec < 2048) {
						$utf = chr(192 + (($dec - ($dec % 64)) / 64));
						$utf .= chr(128 + ($dec % 64));
					} else {
						$utf = chr(224 + (($dec - ($dec % 4096)) / 4096));
						$utf .= chr(128 + ((($dec % 4096) - ($dec % 64)) / 64));
						$utf .= chr(128 + ($dec % 64));
					}

					return $utf;
				}
			}

			return preg_replace_callback('|&#([0-9]{1,});|', 'uft8html2utf8_callback', $s);
		}

		protected function convert_name ($filename = '') {
			$filename = urldecode($filename);
			$filename = $this->uft8html2utf8($filename);
			$filename = preg_replace("/(\]|\[|\@|\"\;\?\=|\"|=|\*|UTF-8|\')/", "", $filename);
			$filename = preg_replace("/(HTTP|http|WWW|www|\.html|\.htm)/i", "", $filename);
			if (empty($filename))
				$filename = substr(md5(time()), 0, 10);
			return $filename;
		}

		protected function get_user_agent () {
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

			return $this->random_elements($agents);
		}

		protected function random_elements ($elements = array ()) {
			if (empty($elements))
				throw new Exception("First arguments is empty");
			return $elements[array_rand($elements)];
		}

		protected function is_admin () {
			return true;
		}

		protected function cut_str ($str, $left, $right) {
			$str = substr(stristr($str, $left), strlen($left));
			$leftLen = strlen(stristr($str, $right));
			$leftLen = $leftLen ? -($leftLen) : strlen($str);
			$str = substr($str, 0, $leftLen);
			return $str;
		}

		protected function size_name ($link = '', $cookie = '') {
			if (!$link || !stristr($link, 'http')) return;
			$link = str_replace(" ", "%20", $link);
			$port = 80;
			$schema = parse_url(trim($link));
			$host = $schema['host'];
			$scheme = "http://";
			if (empty($schema['path'])) return;
			$gach = explode("/", $link);
			list(, $path) = explode($gach[2], $link);

			if (!empty($schema['port'])) {
				$port = $schema['port'];
			}
			elseif ($schema['scheme'] == 'https') {
				$scheme = "ssl://";
				$port = 443;
			}

			$errno = 0;
			$errstr = "";
			if ($scheme != "ssl://") {
				$scheme = "";
			}

			$hosts = $scheme . $host . ':' . $port;
			if ($this->proxy != 0) {
				if (strpos($this->proxy, "|")) {
					list($ip, $user) = explode("|", $this->proxy);
					$auth = base64_encode($user);
				} else $ip = $this->proxy;
				$data = "GET {$link} HTTP/1.1\r\n";
				if (isset($auth))
					$data .= "Proxy-Authorization: Basic $auth\r\n";
				$fp = @stream_socket_client("tcp://{$ip}", $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
			}
			else {
				$data = "GET {$path} HTTP/1.1\r\n";
				$fp = @stream_socket_client($hosts, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
			}
			$data .= "User-Agent: " . $this->get_user_agent() . "\r\n";
			$data .= "Host: {$host}\r\n";
			$data .= $cookie ? "Cookie: $cookie\r\n" : '';
			$data .= "Connection: Close\r\n\r\n";

			if (!$fp) return -1;
			fputs($fp, $data);
			fflush($fp);

			$header = "";
			do {
				if (!$header) {
					$header .= fgets($fp, 8192);
					if (!stristr($header, "HTTP/1"))
						break;
				} else $header .= fgets($fp, 8192);
			} while (strpos($header, "\r\n\r\n") === false);

			if (stristr($header, "TTP/1.0 200 OK") || stristr($header, "TTP/1.1 200 OK") || stristr($header, "TTP/1.1 206")) {
				$filesize = trim($this->cut_str($header, "Content-Length:", "\n"));
			}
			else {
				$filesize = -1;
			}

			if (!is_numeric($filesize)) {
				$filesize = -1;
			}

			$filename = "";
			if (stristr($header, "filename")) {
				$filename = trim($this->cut_str($header, "filename", "\n"));
			}
			else {
				$filename = substr(strrchr($link, '/'), 1);
			}
			$filename = $this->convert_name($filename);

			return array ($filesize, $filename);
		}

		protected function wrong_proxy ($proxy) {
			if (strpos($proxy, "|")) {
				list($prox, $userpass) = explode("|", $proxy);
				list($ip, $port) = explode(":", $prox);
				list($user, $pass) = explode(":", $userpass);
			} else list($ip, $port) = explode(":", $proxy);
			die('<title>You must add this proxy to IDM ' . (strpos($proxy, "|") ? 'IP: ' . $ip . ' Port: ' . $port . ' User: ' . $user . ' & Pass: ' . $pass . '' : 'IP: ' . $ip . ' Port: ' . $port . '') . '</title><center><b><span style="color:#076c4e">You must add this proxy to IDM </span> <span style="color:#30067d">(' . (strpos($proxy, "|") ? 'IP: ' . $ip . ' Port: ' . $port . ' User: ' . $user . ' and Pass: ' . $pass . '' : 'IP: ' . $ip . ' Port: ' . $port . '') . ')</span> <br><span style="color:red">PLEASE REMEMBER: IF YOU DO NOT ADD THE PROXY, YOU CAN NOT DOWNLOAD THIS LINK!</span><br><br>  Open IDM > Downloads > Options.<br><img src="http://i.imgur.com/v7FR3HE.png"><br><br>  Proxy/Socks > Choose "Use Proxy" > Add proxy server: <font color=\'red\'>' . $ip . '</font>, port: <font color=\'red\'>' . $port . '</font> ' . (strpos($proxy, "|") ? ', username: <font color=\'red\'>' . $user . '</font> and password: <font color=\'red\'>' . $pass . '</font>' : '') . ' > Choose http > OK.<br>' . (strpos($proxy, "|") ? '<img src="http://i.imgur.com/LUTpGyN.png">' : '<img src="http://i.imgur.com/zExhNVR.png">') . '<br><br>  Copy your link > Paste in IDM > OK.<br><img src="http://i.imgur.com/S355c5J.png"><br><br>  It will work > Start Download > Enjoy!<br><img src="http://i.imgur.com/vlh2vZf.png"></b></center>');
		}
	}
?>