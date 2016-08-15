<?php
	require_once 'helpers/common.php';
	require_once 'vendor/Client.php';
	require_once 'vendor/RSSVideos.php';

	$post_data = parse_post_data();
	$data = array('status' => false, 'msg' => '');

	//// DEBUG
//	$post_data = array(
//		'rss_link' => 'https://egghead.io/courses/practical-git-for-everyday-professional-use/course_feed?user_email=phudev95%40gmail.com&user_token=61d065de-76e8-4c3a-aa81-6eaf26068de4'
//	);

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