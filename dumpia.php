<?php
class Dumpia {
	private $key;
	private $fanclub;

	const API_BASE = "https://fantia.jp/api/v1";
	const API_POSTS = "/posts/";
	const API_FANCLUBS = "/fanclubs/";

	const ERR_USAGE = "Usage: php dumpia.php --fanclub 1880 --key AbCdEfGhI31Fjwed234";

	public function __construct($key, $fanclub) {
		$this->key = $key;
		$this->fanclub = $fanclub;
	}

	public function go() {
		// stub
	}
}

$cliArgs = array("key:", "fanclub:");

$options = getopt('', $cliArgs);
if(!isset($options['key']) || !isset($options['fanclub'])) {
	echo(Dumpia::ERR_USAGE);
	die();
}

$dumpia = new Dumpia($options['key'], $options['fanclub']);
$dumpia->go();
