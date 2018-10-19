<?php
class Dumpia {
	private $key;
	private $fanclub;

	const CURL_DEBUG = false;
	const HTML_POST_URL_REGEX = "/\/posts\/(?<id>[0-9]{1,8})/";

	const API_POSTS = "https://fantia.jp/api/v1/posts/%s";
	const API_FANCLUB = "https://fantia.jp/api/v1/fanclubs/%s";
	const HTML_POSTLIST = "https://fantia.jp/fanclubs/%s/posts?page=%s";

	const LOG_CURL_FETCHED = "cURL: Got HTTP/%s, received %s bytes.";
	const LOG_POST_EXTRACT = "Extracted %s posts from page %s.";
	const LOG_JSON_OK = "JSON: Decode OK";

	const STR_STARTUP = "dumpia - v0 - https://github.com/itskenny0/dumpia";

	const ERR_USAGE = "Usage: php dumpia.php --fanclub 1880 --key AbCdEfGhI31Fjwed234";
	const ERR_API_NO_JSON = "Invalid API response (JSON decode failed) - API said: ";
	const ERR_API_HTTP_NOK = "Got HTTP/%s - unable to fetch page.";
	const ERR_API_NO_MATCHES = "Could not find any post URLs in the gallery. Possibly this fanclub has no posts or the format changed.";

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

	private function fetch($url) {
		self::log("cURL: $url");
		$c = curl_init($url);

		curl_setopt($c, CURLOPT_COOKIE, '_session_id=' . $this->key . ';');
		if(self::CURL_DEBUG) curl_setopt($c, CURLOPT_VERBOSE, 1);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

		$out = curl_exec($c);
		$httpcode = curl_getinfo($c, CURLINFO_HTTP_CODE);
		curl_close($c);

		if($httpcode != 200) throw new Exception(sprintf(self::ERR_API_HTTP_NOK, $httpcode));

		self::log(sprintf(self::LOG_CURL_FETCHED, $httpcode, strlen($out)));

		return $out;
	}

	private function fetchJSON($type, $id) {
		$url = sprintf($type, $id);
		$out = $this->fetch($url);

		$outJ = json_decode($out);
		if(!is_object($outJ)) throw new Exception(self::ERR_API_NO_JSON . $out);
		self::log(self::LOG_JSON_OK);

		return $outJ;
	}

	private function fetchGalleryPage($page) {
		$url = sprintf(self::HTML_POSTLIST, $this->fanclub, $page);
		$html = $this->fetch($url);

		$posts = $this->htmlExtractPosts($html);

		self::log(sprintf(self::LOG_POST_EXTRACT, count($posts), $page));
		return $posts;
	}

	private function htmlExtractPosts($html) {
		preg_match_all(self::HTML_POST_URL_REGEX, $html, $out);

		if(empty($out['id'])) throw new Exception(self::ERR_API_NO_MATCHES);

		return $out['id'];
	}


	private function getPostPhotos($id) {
		$out = $this->fetchJSON(self::API_POSTS, $id);

		$results = array();
		foreach($out->post->post_contents as $c) {
			foreach($c->post_content_photos as $i) {
				$url = $i->url->original ?: $i->url->main ?: $i->url->large ?: $i->url->medium;
				if(empty($url)) self::log("Unable to find URL for " . $i->id);
				$results[] = $url;
			}
		}

		return $results;
	}

	public function go() {
		self::log(self::STR_STARTUP);

		$page = 1; // start at pg1
		$results = array();

		try {

			while(true) {
				self::log("Fetching page $page ...");
				$out = $this->fetchGalleryPage($page);
				$results = array_merge($results, $out);
				$page++;
			}

		} catch (Exception $e) {

			$ct = count($results);
			self::log("No matches on page $page. Last fetchable page reached - downloading $ct posts.");

		}


		$urlsByPost = array();
		foreach($results as $id) {
			self::log("Fetching metadata (JSON) for post $id ...");

			$out = $this->getPostPhotos($id);
			$urlsByPost[$id] = $out;

			self::log("Added " . count($out) . " URLs for post $id.");
		}
	}
}

$cliArgs = array("key:", "fanclub:");

$options = getopt('', $cliArgs);
if(!isset($options['key']) || !isset($options['fanclub'])) {
	echo(Dumpia::ERR_USAGE . PHP_EOL);
	die();
}

$dumpia = new Dumpia($options['key'], $options['fanclub']);
$dumpia->go();
