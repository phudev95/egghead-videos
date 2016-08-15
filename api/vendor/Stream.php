<?php
	require_once 'vendor/StreamHelper.php';

	class Stream extends StreamHelper {
		protected $cookie = '';
		protected $unit = 512;

		/**
		 * Response to client with streamline download
		 * @param array $parameters
		 */
		protected function stream_download ($parameters = array()) {
			$filesize = $parameters['size'];
			$filename = $this->convert_name($parameters['filename']);
			$link = str_replace(" ", "%20", $parameters['url']);

			if (!$link) {
				sleep(15);
				header("HTTP/1.1 404 Not Found");
				die('Account/Cookie Error !!!');
			}

			if (!empty($parameters['proxy'])) {
				list($ip,) = explode(":", $parameters['proxy']);
				if ($_SERVER['REMOTE_ADDR'] != $ip) {
					$this->wrong_proxy($parameters['proxy']);
				} else {
					header('Location: ' . $link);
					die;
				}
			}

			$range = '';
			$new_length = 0;
			if (isset($_SERVER['HTTP_RANGE'])) {
				$range = substr($_SERVER['HTTP_RANGE'], 6);
				list($start, ) = explode('-', $range);
				$new_length = $filesize - $start;
			}

			$port = 80;
			$schema = parse_url(trim($link));
			$host = $schema['host'];
			$scheme = "http://";
			$gach = explode("/", $link);
			list(, $path) = explode($gach[2], $link);

			if (!empty($schema['port']))
				$port = $schema['port']; elseif ($schema['scheme'] == 'https') {
				$scheme = "ssl://";
				$port = 443;
			}
			if ($scheme != "ssl://") {
				$scheme = "";
			}

			$hosts = $scheme . $host . ':' . $port;
			if (!empty($parameters['proxy'])) {
				if (strpos($parameters['proxy'], "|")) {
					list($ip, $user) = explode("|", $parameters['proxy']);
					$auth = base64_encode($user);
				}
				else {
					$ip = $parameters['proxy'];
				}

				$data = "GET {$link} HTTP/1.1\r\n";
				if (isset($auth)) {
					$data .= "Proxy-Authorization: Basic $auth\r\n";
				}

				$fp = @stream_socket_client("tcp://{$ip}", $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
			} else {
				$data = "GET {$path} HTTP/1.1\r\n";
				$fp = @stream_socket_client($hosts, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
			}

			if (!$fp) {
				sleep(15);
				header("HTTP/1.1 404 Not Found");
				die("HTTP/1.1 404 Not Found");
			}

			// Init Header for Stream
			$data .= "User-Agent: " . $this->get_user_agent() . "\r\n";
			$data .= "Host: {$host}\r\n";
			$data .= "Accept: */*\r\n";
			$data .= $this->cookie ? "Cookie: " . $this->cookie . "\r\n" : '';
			if (!empty($range)) {
				$data .= "Range: bytes={$range}\r\n";
			}
			$data .= "Connection: Close\r\n\r\n";
			@stream_set_timeout($fp, 2);
			fputs($fp, $data);
			fflush($fp);

			$header = '';
			do {
				if (!$header) {
					$header .= stream_get_line($fp, $this->unit);
					if (!stristr($header, "HTTP/1"))
						break;
				}
				else {
					$header .= stream_get_line($fp, $this->unit);
				}
			} while (strpos($header, "\r\n\r\n") === false);

			// Debug
			if ($this->is_admin() && !empty($_GET['debug'])) {
				// Uncomment next line for enable to admins this debug code.
				echo "<pre>Connected to : $hosts " . ($parameters['proxy'] == 0 ? '' : "via {$parameters['proxy']}") . "\r\n{$data}\r\n\r\nServer replied: \r\n{$header}</pre>";
				die();
			}

			// Must be fresh start
			if (headers_sent()) die('Headers Sent');

			// Required for some browsers
			if (ini_get('zlib.output_compression')) ini_set('zlib.output_compression', 'Off');

			// Grant permission to Header of browser
			header("Pragma: public"); // required
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private", false); // required for certain browsers
			header("Content-Transfer-Encoding: binary");
			header("Accept-Ranges: bytes");


			if (stristr($header, "TTP/1.0 200 OK") || stristr($header, "TTP/1.1 200 OK")) {
				if (!is_numeric($filesize))
					$filesize = trim($this->cut_str($header, "Content-Length:", "\n"));
				if (stristr($header, "filename")) {
					$filename = trim($this->cut_str($header, "filename", "\n"));
					$filename = preg_replace("/(\"\;\?\=|\"|=|\*|UTF-8|\')/", "", $filename);
				}
				if (is_numeric($filesize)) {
					header("HTTP/1.1 200 OK");
					header("Content-Type: application/force-download");
					header("Content-Disposition: attachment; filename=" . $filename);
					header("Content-Length: {$filesize}");
				}
				else {
					sleep(5);
					header("HTTP/1.1 404 Not Found");
					die("HTTP/1.1 404 Not Found");
				}
			}
			elseif (stristr($header, "TTP/1.1 206") || stristr($header, "TTP/1.0 206")) {
				sleep(2);
				header("HTTP/1.1 206 Partial Content");
				header("Content-Type: application/force-download");
				header("Content-Length: $new_length");
				header("Content-Range: bytes $range/{$filesize}");
			}
			else {
				sleep(10);
				header("HTTP/1.1 404 Not Found");
				die("HTTP/1.1 404 Not Found");
			}
			$tmp = explode("\r\n\r\n", $header);
			$max = count($tmp);
			for ($i = 1; $i < $max; $i++) {
				print $tmp[$i];
				if ($i != $max - 1)
					echo "\r\n\r\n";
			}
			while (!feof($fp) && (connection_status() == 0)) {
				$recv = @stream_get_line($fp, $this->unit);
				print $recv;
				@flush();
				@ob_flush();
			}
			fclose($fp);
			exit;
		}

		/**
		 * Init stream download
		 * @param string $url
		 * @param string $name
		 */
		public function download ($url = '', $name = '') {
			if (!$url) {
				sleep(15);
				header("HTTP/1.1 404 Not Found");
				die('URL is empty!');
			}

			// Get file size + file name
			$size_name = $this->size_name($url);
			preg_match("/^(.*\.[^?]+).*?$/", $size_name[1], $matches);

			// Config and stream now
			$filename = !empty($name) ? $name : $matches[1];
			$parameters = array(
				'filename' => $filename,
				'url' => $url,
				'proxy' => '',
				'size' => $matches[0]
			);

			$this->stream_download($parameters);
		}
	}
?>