<?php
header("Access-Control-Allow-Origin: *");
require_once('yellowpages-scraper.php');

function scrapeMoreInfo($moreinfo) {
	$scrapeClass = "YellowpagesScraper";
	$source = Utils\getHTML($moreinfo);
	$html = str_get_html($source);

	if ($html) {
		$name = $_REQUEST['name'];

		$location = call_user_func( array($scrapeClass, 'get_location'), $html);
		$phone = call_user_func( array($scrapeClass, 'get_phone'), $html);
		
		$address 	 = "";
		$description = "";
		$website 	 = "";
		$categories  = array();
		$emails 	 = array();

		if($location!=null && $phone!=null) {
			$address 	 = call_user_func( array($scrapeClass, 'get_address'), $html);
			$description = call_user_func( array($scrapeClass, 'get_description'), $html);
			$categories  = call_user_func( array($scrapeClass, 'get_categories'), $html);
			$website 	 = call_user_func( array($scrapeClass, 'get_website'), $html);

			$yellowpageEmails = array_map(
				function($element) { return str_ireplace('mailto:', '', $element->href); },
				$html->find('.email-business')
			);
			if (isset($yellowpageEmails) && count($yellowpageEmails) > 0) {
				$emails = array_merge($emails, $yellowpageEmails);
			}
		}

		$html->clear();
		unset($html);

		$response = array(
			"name"			=> $name,
			"email"			=> $emails,
			"website"		=> $website,
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
