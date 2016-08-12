<?php
	require_once 'helpers/Client.php';
	require_once 'helpers/common.php';

	$post_data = parse_post_data();
	$data = array();

	if (!empty($post_data['rss_link'])) {
		$rss_link = $post_data['rss_link'];

		// Request rss link
		$browser = browser_request();
	    $browser->setReferer('https://egghead.io/');
	    $browser->execute($rss_link);
	    throwRequestError($browser);
	    $xml_string = $browser->getResponseText();

		// Parse xml to json
		$itunes = new iTunesXMLParser();
		$itunes->parse( $xml_string );
		echo "<pre>".print_r($itunes, 1)."</pre>";die;

//		$xml = simplexml_load_string($xml_string);
//		$json = json_encode($xml);

		$data['status'] = 'ok';
		$data['data'] = $json;
	}
	else {
		$data['status'] = 'nok';
		$data['msg'] = 'Missing parameter: rss_link';
	}

	response_json($data);

?>