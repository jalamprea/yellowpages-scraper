<?php
header("Access-Control-Allow-Origin: *");
require_once('yellowpages-scraper.php');

function scrapeMoreInfo($moreinfo) {
	$scrapeClass = "YellowpagesScraper";
	$source = Utils\getHTML($moreinfo);
	$html = str_get_html($source);

	if ($html) {
		$name = $_REQUEST['name'];

		$yellowpageEmails = array_map(
			function($element) { return str_ireplace('mailto:', '', $element->href); },
			$html->find('.email-business')
		);
		$emails = array();
		if (isset($yellowpageEmails) && count($yellowpageEmails) > 0) {
			$emails = array_merge($emails, $yellowpageEmails);
		}

		$address = call_user_func( array($scrapeClass, 'get_address'), $html);
		$location = call_user_func( array($scrapeClass, 'get_location'), $html);
		$phone = call_user_func( array($scrapeClass, 'get_phone'), $html);
		$description = "";
		$categories = array();
		if($location!=null && $phone!=null) {
			$description = call_user_func( array($scrapeClass, 'get_description'), $html);
			$categories = call_user_func( array($scrapeClass, 'get_categories'), $html);
		}

		$html->clear();
		unset($html);

		$response = array(
			"name"			=> $name,
			"emails"		=> $emails,
			"address"		=> $address,
			"location"		=> $location,
			"phone"			=> $phone,
			"description"	=> $description,
			"categories"	=> $categories
		);
		header('Content-type: application/json');
		echo (json_encode($response));
	}
}

if( isset($_REQUEST['moreinfo']) ) {
	$info_url = $_REQUEST['moreinfo'];
	scrapeMoreInfo($info_url);
}
