<?php
	require_once 'helpers/Client.php';
	require_once 'helpers/common.php';

	$rss_link = 'https://egghead.io/courses/practical-git-for-everyday-professional-use/course_feed?user_email=phudev95%40gmail.com&user_token=61d065de-76e8-4c3a-aa81-6eaf26068de4';

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
	$doc->loadXML($xml_string);

	// Initialize XPath
	$xpath = new DOMXpath($doc);

	// Register the itunes namespace
	$xpath->registerNamespace('itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
	$items = $doc->getElementsByTagName('item');

    $course_title = $doc->getElementsByTagName( "title" )->item(0)->nodeValue;

	$results = [
		'title' => str_replace('egghead.io course feed: ', '', $course_title),
		'items' => []
	];
	foreach ($items as $item) {
		$title = $xpath->query('title', $item)->item(0)->nodeValue;
		$author = $xpath->query('itunes:author', $item)->item(0)->nodeValue;
		$duration = $xpath->query('itunes:duration', $item)->item(0)->nodeValue;

		// Enclosure tag
		$enclosure = $xpath->query('enclosure', $item)->item(0);
		$video_source = $enclosure->attributes->getNamedItem('url')->value;
		$video_length = $enclosure->attributes->getNamedItem('length')->value;
		$video_type = $enclosure->attributes->getNamedItem('type')->value;

		// Push item to results array
		$results['items'][] = array (
			'title' => $title,
			'author' => $author,
			'duration' => $duration,
			'video_source' => $video_source,
			'video_length' => $video_length,
			'video_type' => $video_type
		);
	}

	response_json($results);
?>