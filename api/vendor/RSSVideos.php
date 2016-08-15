<?php
	class RSSVideos {
		/**
		 * Request get rss xml
		 * @param string $rss_link
		 * @return string
		 */
		protected function get_xml ($rss_link='') {
			$browser = browser_request();
		    $browser->setReferer('https://egghead.io/');
		    $browser->execute($rss_link);
		    throwRequestError($browser);
		    return $browser->getResponseText();
		}

		/**
		 * Parse XML
		 * @param string $xml
		 * @return array
		 */
		protected function parse_xml ($xml='') {
			// Initialize DOMDocument
			// https://www.ibm.com/developerworks/vn/library/os-xmldomphp/
			$doc = new DOMDocument();
			$doc->preserveWhiteSpace = false;

			if (@$doc->loadXML($xml)) {
				// Initialize XPath
				$xpath = new DOMXpath($doc);

				// Register the itunes namespace
				$xpath->registerNamespace('itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
				$items = $doc->getElementsByTagName('item');

				$course_title = $doc->getElementsByTagName("title")->item(0)->nodeValue;
				$course_link = $doc->getElementsByTagName("link")->item(0)->nodeValue;

				$temp = array(
					'title' => str_replace('egghead.io course feed: ', '', $course_title),
					'link' => $course_link,
					'items' => array()
				);

				foreach ($items as $item) {
					$title = $xpath->query('title', $item)->item(0)->nodeValue;
					$parse_title = parse_title($title);
					$author = $xpath->query('itunes:author', $item)->item(0)->nodeValue;
					$duration = $xpath->query('itunes:duration', $item)->item(0)->nodeValue;
					$link = $xpath->query('guid', $item)->item(0)->nodeValue;

					// Enclosure tag
					$enclosure = $xpath->query('enclosure', $item)->item(0);
					$video_source = $enclosure->attributes->getNamedItem('url')->value;
					$video_length = (int) $enclosure->attributes->getNamedItem('length')->value;
					$video_type = $enclosure->attributes->getNamedItem('type')->value;

					// Push item to results array
					$temp['items'][$link] = array (
						'title' => $parse_title['title'],
						'category' => $parse_title['category'],
						'author' => $author,
						'duration' => gmdate("H:i:s", $duration),
						'source' => $video_source,
						'length' => formatBytes($video_length),
						'type' => $video_type,
						'link' => $link
					);
				}

				return $temp;
			}
			else {
				throwRequestError('XML in valid!');
			}
		}

		/**
		 * Exec
		 * @param string $rss_link
		 * @return array
		 * @throws Exception
		 */
		public function exec ($rss_link='') {
			if (!empty($rss_link)) {
				$xml = $this->get_xml($rss_link);
				$data = $this->parse_xml($xml);
				$course_link = $data['link'];

				// Request course link and get order all of lessons
				$browser = browser_request();
			    $browser->setReferer('https://egghead.io/');
			    $browser->execute($course_link);
			    throwRequestError($browser);
			    $res_html = $browser->getResponseText();

				// Sort by lesson link
				$temp = array();
				$lesson_order_list = $this->get_order_of_courses($res_html);
				if (!empty($lesson_order_list)) {
					foreach ($lesson_order_list as $index => $link) {
						if (!empty($data['items'][$link])) {
							$video_source =& $data['items'][$link]['source'];
							$video_source = get_stream_link(
								($index + 1),
								$data['items'][$link]['title'],
								$data['items'][$link]['type'],
								$video_source
							);
							$temp[] = $data['items'][$link];
						}
					}

					// Overwrite items
					$data['items'] = $temp;
				}

				return $data;
			}
			else {
				throw new Exception('RSS link is empty!');
			}
		}

		/**
		 * Get order of course by ASC
		 * @param string $html
		 * @return array
		 */
		protected function get_order_of_courses ($html=''){
			//$path = dirname(__FILE__) . '\\' . 'data.html';
			//$html = file_get_contents($path);
			$lesson_list = cut_str($html, 'id="lesson-list"', 'class="table-responsive"');
			$lesson_order_list = array();

			preg_match_all('/<a.*href=\"([^?"]+)/i', $lesson_list, $matches);
			if (!empty($matches[1])) {
				$lesson_order_list = $matches[1];
			}

			return $lesson_order_list;
		}
	}

?>