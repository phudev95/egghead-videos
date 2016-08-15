<?php
	require_once 'helpers/common.php';
	require_once 'vendor/Stream.php';


	if (!empty($_GET['src']) && !empty($_GET['name'])) {
		$src = urldecode($_GET['src']);
		$name = urldecode($_GET['name']);

		$stream = new Stream();
		$stream->download($src, $name);
	}