<?php
	ob_start();
	ob_implicit_flush(true);
	ignore_user_abort(0);
	if (!ini_get('safe_mode')) set_time_limit(30);

	require_once 'helpers/common.php';
	require_once 'vendor/Stream.php';

	if (!empty($_GET['src']) && !empty($_GET['name'])) {
		$src = urldecode($_GET['src']);
		$name = urldecode($_GET['name']);

		$stream = new Stream();
		$stream->download($src, $name);
	}

	ob_end_flush();