<?php
class Dumpia {
	private $key;
	private $fanclub;

	const CURL_DEBUG = false;

	const API_BASE = "https://fantia.jp/api/v1";
	const API_POSTS = "/posts/";
	const API_FANCLUBS = "/fanclubs/";

	const STR_STARTUP = "dumpia - v0 - https://github.com/itskenny0/dumpia";

	const ERR_USAGE = "Usage: php dumpia.php --fanclub 1880 --key AbCdEfGhI31Fjwed234";
	const ERR_API_NO_JSON = "Invalid API response (JSON decode failed) - API said: ";

	/* --------------------------------------------- */

	private static function log($msg) {
		$pid = getmypid();
		$date = date("r");

		$line = "[$date][$pid] $msg" . PHP_EOL;
		echo $line;
	}

	/* --------------------------------------------- */

	public function __construct($key, $fanclub) {
		$this->key = $key;
		$this->fanclub = $fanclub;
	}

	private function fetch($type, $id) {
		self::log("cURL: " . self::API_BASE . $type . $id);
		$c = curl_init(self::API_BASE . $type . $id);

		curl_setopt($c, CURLOPT_COOKIE, '_session_id=' . $this->key . ';');
		if(self::CURL_DEBUG) curl_setopt($c, CURLOPT_VERBOSE, 1);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

		$out = curl_exec($c);
		curl_close($c);

		self::log("cURL: fetched - received " . strlen($out) . " bytes.");

		$outJ = json_decode($out);
		if(!is_object($outJ)) throw new Exception(self::ERR_API_NO_JSON . $out);
		self::log("JSON decode OK");

		return $outJ;
	}

	public function go() {
		self::log(self::STR_STARTUP);

		self::log("Fetching index ...");
		$index = $this->fetch(self::API_FANCLUBS, $this->fanclub);

		self::log("Downloaded index!");
		self::log("-> Title: " . $index->fanclub->title);
		self::log("-> " . count($index->fanclub->recent_posts) . " recent posts");
		print_r($index);
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
