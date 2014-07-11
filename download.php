<?php

/**
 * Quickly fetch torrents from PirateBay and add them into uTorrent
 * 
 * To use:
 * - fill in 'token', 'cookie' and 'userpwd' details in "addMagnetTouTorrent()" function
 * - adjust '$url' to fetch data from in "getUrl()" function
 * 
 * @author Jordan Skoblenick <parkinglotlust@gmail.com> 2014-07-11
 */

$page = 1;
$maxLoops = 1000; // prevent infinite loops

while ($maxLoops--) {
	if (!$response = file_get_contents(getUrl($page))) {
		die('Empty response for URL: '.$api);
	}
	if (!$json = json_decode($response)) {
		die('JSON failed to decode: '.$response);
	}
	
	// check for last page
	if ($json->query->count == 0) {
		echo "\n\nNo more results - must be all done!\n";
		die;
	}
	// check json structure
	if (!$torrentInfo = @$json->query->results->td) {
		die('Bad JSON structure: '.print_r($json, true));
	}
	
	echo "\nPage {$page}\n";
	foreach ($torrentInfo as $torrent) {
		$filename = $torrent->div->a->content;
		
		if (is_array($torrent->a)) {
			$magnet = $torrent->a[0]->href; // first index will be "magnet"
		}
		else {
			$magnet = $torrent->a->href;
		}

		echo "add {$filename}\n";
		addMagnetLinkTouTorrent($magnet);
	}
	
	$page++;
}

function getUrl($page) {
	$url = "http://thepiratebay.se/user/xxx/{$page}/3";
	$xpath = '//a[@title="Download this torrent using magnet"]/..';

	$yql = "select * from html where url='{$url}' and xpath='{$xpath}'";
	$api = 'https://query.yahooapis.com/v1/public/yql?format=json&diagnostics=false&callback=&q='.urlencode($yql);
	
	return $api;
}

function addMagnetLinkTouTorrent($magnetLink) {
	$data = [
	    'action' => 'add-url',
	    's' => $magnetLink,
	    'token' => 'xxxx' // from http://127.0.0.1:9090/gui/token.html
	];
	$query = http_build_query($data, '', '&');
	
	$uTorrent = 'http://127.0.0.1:9090/gui/?'.$query;
	$ch = curl_init($uTorrent);

	curl_setopt_array($ch, array(
	    CURLOPT_RETURNTRANSFER => true,
	    CURLOPT_SSL_VERIFYPEER => false,
	    CURLOPT_SSL_VERIFYHOST => false,
	    CURLOPT_COOKIE => 'GUID=xxxx',
	    CURLOPT_USERPWD => 'xxxx:xxxx',
	    CURLOPT_HTTPHEADER => ['Content-Type: multipart/form-data']
	));

	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
	
	if ($info['http_code'] == 200) {
		return true;
	}
	
	var_dump($response, $info); die('Something went wrong adding magnet link');
}