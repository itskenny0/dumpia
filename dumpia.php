<?php
class Dumpia {
	private $key;
	private $fanclub;
	private $output;
	private $verbose = false;

	const CURL_DEBUG = false;
	
	/* Fantia endpoints */
	const API_POSTS = "https://fantia.jp/api/v1/posts/%s";
	const API_FANCLUB = "https://fantia.jp/api/v1/fanclubs/%s";
	const HTML_POSTLIST = "https://fantia.jp/fanclubs/%s/posts?page=%s";

	/* Log format and strings */
	const LOG_FORMAT = "[%s][%s] %s" . PHP_EOL; // $date, $pid, $msg
	
	const LOG_STARTUP = "dumpia - v0.2 - https://github.com/itskenny0/dumpia";
	const LOG_CURL_FETCHED = "cURL: Got HTTP/%s, received %s bytes.";
	const LOG_POST_EXTRACT = "Extracted %s posts from page %s.";
	const LOG_JSON_OK = "JSON: Decode OK";
	const LOG_LAST_PAGE = "No matches on page %s. Last fetchable page reached.";
	const LOG_DOWNLOAD_BEGIN = "Trying %s posts ...";
	const LOG_FETCH_PAGE = "Attempting to fetch page %s...";
	const LOG_FETCH_METADATA = "Fetching metadata (JSON) for post %s ...";
	const LOG_URL_LIST = "Downloading %s URLs for post %s...";
	const LOG_NO_URL = "Unable to find URL for %s";
	const LOG_CURL = "cURL: %s";
	const LOG_NO_PHOTOS = "No photos found in post %s (post_content_photos is empty).";
	const LOG_DOWNLOAD_FIN = "Download finished.";
	const LOG_DOWNLOAD_SKIPPED = "The following posts had no downloadable photos and were skipped: %s";

	const ERR_USAGE = "Usage: php dumpia.php --fanclub 1880 --key AbCdEfGhI31Fjwed234 --output /home/user/dumpia/ [--verbose]";
	const ERR_DIR_NOT_EXIST = "The given output directory does not exist.";
	const ERR_API_NO_JSON = "Invalid API response (JSON decode failed) - API said: ";
	const ERR_API_HTTP_NOK = "Got HTTP/%s - unable to fetch page.";
	const ERR_API_NO_MATCHES = "Could not find any post URLs in the gallery. Possibly this fanclub has no posts or the format changed.";
	
	/* Extraction regexes */
	const HTML_POST_URL_REGEX = "/\/posts\/(?<id>[0-9]{1,8})/";
	const FILENAME_REGEX = "/\/(?<name>[^\/]+\.(jpg|jpeg|png|svg|bmp))/i";

	public function __construct($options) {
		$this->key = $options['key'];
		$this->fanclub = $options['fanclub'];
		$this->output = $options['output'];
		if(isset($options['verbose'])) $this->verbose = true;
	}
	
	public function main() {
		self::log(self::LOG_STARTUP);

		$page = 1; // start at pg1
		$results = array();

		try {

			while(true) {
				self::log(sprintf(self::LOG_FETCH_PAGE, $page));
				$out = $this->fetchGalleryPage($page); // load gallery (list of post IDs) - extracted from HTML - to do: find the REST endpoint for this, assuming it exists
				$results = array_merge($results, $out); // add all pages' post IDs into one array
				$page++;
			}

		} catch (Exception $e) {

			// exception triggered by fetchGalleryPage indicating the last page was reached
			$ct = count($results);
			if($this->verbose) self::log(sprintf(self::LOG_LAST_PAGE, $page));

		}
		
		self::log(sprintf(self::LOG_DOWNLOAD_BEGIN, $ct));

		$emptyPosts = array(); // if posts are skipped because there is no content in it, the post ID will be added to this array
		$urlsByPost = array();
		$countTotal = 0;

		foreach($results as $id) {
			if($this->verbose) self::log(sprintf(self::LOG_FETCH_METADATA, $id));

			$out = $this->getPostPhotos($id); // get list of raw URLs from REST API
			
			if(empty($out)) {
				if($this->verbose) self::log(sprintf(self::LOG_NO_PHOTOS, $id));
				$emptyPosts[] = $id; // collect empty posts for output after completion
				continue; // if no photos in post, skip to next one				
			}

			$count = count($out);
			$countTotal += $count;
			if($this->verbose) self::log(sprintf(self::LOG_URL_LIST, $count, $id));

			$this->download($this->output . '/' . $id, $out); // download images into a folder named after the post ID
		}
		
		self::log(self::LOG_DOWNLOAD_FIN);
		if(!empty($emptyPosts)) self::log(sprintf(self::LOG_DOWNLOAD_SKIPPED, implode(" ", $emptyPosts)));
	}
	
	private static function log($msg) {
		$pid = getmypid();
		$date = date("r");

		$line = sprintf(self::LOG_FORMAT, $date, $pid, $msg);
		echo $line;
	}
	
	private function fetch($url) {
		if($this->verbose) self::log(sprintf(self::LOG_CURL, $url));
		$c = curl_init($url);

		curl_setopt($c, CURLOPT_COOKIE, '_session_id=' . $this->key . ';'); // sets the sessid cookie to the value of --key at CLI
		if(self::CURL_DEBUG) curl_setopt($c, CURLOPT_VERBOSE, 1);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

		$out = curl_exec($c);
		$httpcode = curl_getinfo($c, CURLINFO_HTTP_CODE); // get http code to verify fetch was successful
		curl_close($c);

		if($httpcode != 200) throw new Exception(sprintf(self::ERR_API_HTTP_NOK, $httpcode));

		if($this->verbose) self::log(sprintf(self::LOG_CURL_FETCHED, $httpcode, strlen($out)));

		return $out;
	}

	private function fetchJSON($type, $id) {
		$url = sprintf($type, $id);
		$out = $this->fetch($url);

		$outJ = json_decode($out);
		if(!is_object($outJ)) throw new Exception(self::ERR_API_NO_JSON . $out);
		if($this->verbose) self::log(self::LOG_JSON_OK);

		return $outJ;
	}

	private function fetchGalleryPage($page) {
		$url = sprintf(self::HTML_POSTLIST, $this->fanclub, $page);
		$html = $this->fetch($url);

		$posts = $this->htmlExtractPosts($html);

		if($this->verbose) self::log(sprintf(self::LOG_POST_EXTRACT, count($posts), $page));
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
		if(isset($out->post->thumb)) $results[] = $out->post->thumb->original ?: $out->post->thumb->main ?: $out->post->thumb->large ?: $out->post->thumb->medium; // cover image
		
		if(empty($out->post->post_contents)) return $results;
		
		foreach($out->post->post_contents as $c) {
			if(empty($c->post_content_photos)) continue;
			
			foreach($c->post_content_photos as $i) {
				$url = $i->url->original ?: $i->url->main ?: $i->url->large ?: $i->url->medium;
				if(empty($url)) self::log(sprintf(self::LOG_NO_URL, $i->id));
				$results[] = $url;
			}
		}
		
		return $results;
	}

	private function download($folder, $urls) {
		if(!is_dir($folder)) mkdir($folder);
		$ct = count($urls);
		echo "$folder ($ct URLs): ";

		foreach($urls as $n => $u) {
			preg_match(self::FILENAME_REGEX, $u, $out);

			if(empty($out['name'])) {
				echo $u;
				echo "-";
				continue;
			}
			
			$name = $out['name'];
			if(@copy($u, "$folder/$name")) echo ".";
			else echo "!";
		}

		echo PHP_EOL;
	}
}

$cliArgs = array("key:", "fanclub:", "output:", "verbose");

$options = getopt('', $cliArgs);
if(!isset($options['key']) || !isset($options['fanclub']) || !isset($options['output'])) {
	echo(Dumpia::ERR_USAGE . PHP_EOL);
	die();
}

if(!is_dir($options['output'])) {
	echo(Dumpia::ERR_DIR_NOT_EXIST . PHP_EOL);
	die();
}

$dumpia = new Dumpia($options);
$dumpia->main();
