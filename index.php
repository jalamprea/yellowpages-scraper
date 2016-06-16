<?php
header("Access-Control-Allow-Origin: *");
// require_once('ThreadScraper.php'); //used only in PHP phthread environment as a CLI application
require_once('yellowpages-scraper.php');
///require_once('stik-scraper.php');

if( isset($_REQUEST['ajax']) ) {
	define('AJAX_QUERY', 1);
} else {
	echo "Memory: ".memory_get_usage() . "\n";
}

YellowpagesScraper::run();
// StikScraper::run();

if( !isset($_REQUEST['ajax']) ) 
	echo "Memory: ".memory_get_usage() . "\n";
?>