<?php
	require_once 'helpers/Client.php';
	require_once 'helpers/common.php';

	$post_data = parse_post_data();
	$data = array('status' => false, 'msg' => '');

	if (!empty($post_data['rss_link'])) {
		$rss_link = $post_data['rss_link'];

		// Request rss link
		$browser = browser_request();
	    $browser->setReferer('https://egghead.io/');
	    $browser->execute($rss_link);
	    throwRequestError($browser);
	    $xml_string = $browser->getResponseText();

		// Initialize DOMDocument
		// https://www.ibm.com/developerworks/vn/library/os-xmldomphp/
		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;

		if (@$doc->loadXML($xml_string)) {
			// Initialize XPath
			$xpath = new DOMXpath($doc);

			// Register the itunes namespace
			$xpath->registerNamespace('itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
			$items = $doc->getElementsByTagName('item');

		    $course_title = $doc->getElementsByTagName( "title" )->item(0)->nodeValue;

			$results = array(
				'title' => str_replace('egghead.io course feed: ', '', $course_title),
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
				$results['items'][] = array (
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

			$data['status'] = true;
			$data['data'] = $results;
		}
		else {
			$data['msg'] = 'XML is invalid!!!';
		}
	}
	else {
		$data['msg'] = 'Missing parameter: rss_link';
	}

	response_json($data);

?>