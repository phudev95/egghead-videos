<?php
	require_once 'helpers/common.php';
	require_once 'vendor/Client.php';
	require_once 'vendor/RSSVideos.php';

	$post_data = parse_post_data();
	$result = array();
	$start = microtime(true);

	try {
		$RSSVideos = new RSSVideos();
		$result = array(
			'status' => true,
			'data' => $RSSVideos->exec($post_data['rss_link'])
		);
	} catch (Exception $e) {
		$result = array(
			'status' => false,
			'msg' => 'Error: ' . $e->getMessage()
		);
	}

	$result['time_elapsed_secs'] = microtime(true) - $start;
	response_json($result);
?>