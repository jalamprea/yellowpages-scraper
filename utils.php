<?php
namespace Utils;

require_once 'phpwhois-4.2.2/whois.main.php';

function getHTML($url, $data = null, $post = false, $cookies = false) {
    $curl = curl_init();

    if ($post) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    } else {
        $url .= $data === null ? '' : '?' . http_build_query($data);
    }

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    if ($cookies) {
        $tmpfname = 'cookie.txt';
        curl_setopt($curl, CURLOPT_COOKIEJAR, $tmpfname);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $tmpfname);
    }

    if (!$result = curl_exec($curl)) {
        // echo curl_error($curl) . "\n";
    }

    curl_close($curl);

    return $result;
}

function get_whois_emails(&$emails, $domain) {
    $whois = new \Whois();
    $result = @$whois->Lookup($domain);
    $whoisemails = array_find_emails('email', $result['rawdata']);
    if ($whoisemails && count($whoisemails) > 0)
        $emails = array_merge($emails, $whoisemails);
}

function array_find_emails($needle, $haystack) {
    $array = array();

    foreach ($haystack as $item)
    {
        if (stripos($item, $needle) !== false) {
            $emails = array_filter(
                array_map(
                    function($word) { return filter_var($word, FILTER_VALIDATE_EMAIL); },
                    explode(' ', $item)
                ),
                'Utils\isRelevantEmail'
            );

            $array = array_merge($array, $emails);
        }
    }

    return count($array) > 0 ? $array : false;
}

function isRelevantEmail($email) {
    return $email !== false 
        && 0 === count(array_filter(array_map(
            function($value) use ($email) {
                return stripos($email, $value) === false;
            },
            array(
                'host',
                'whois',
                'domain',
                'dns',
                'no.valid.email@worldnic.com',
                'customerservice@networksolutions.com',
                'contact@myprivateregistration.com',
                'networksolutionsprivateregistration.com',
                'admin@internationaladmin.com',
                'contact.gandi.net',
                'abuse'
            )
        )));
}


function get_more_info_lead(&$emails, &$address, &$location, &$phone, &$description, &$categories, $moreinfo, $scrapeClass) {
    
    /* $moreinfo_url = 'http://'.$_SERVER['HTTP_HOST'].'/'.$_SERVER['REQUEST_URI'];
    $moreinfo_url.= 'moreinfo-scraper.php?moreinfo='.urlencode($moreinfo);

    $curl = curl_init();
    // Set some options - we are passing in a useragent too here
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $moreinfo_url,
        CURLOPT_USERAGENT => 'Simple Scraper PHP Application'
    ));
    // Send the request & save response to $resp
    if (!$res = curl_exec($curl)) {
        die( curl_error($curl) );
    }
    // Close request to clear up some resources
    curl_close($curl);

    $data = json_decode($res, true);
    unset($curl);

    if(isset($data['emails'])) {
        if(is_string($data['emails'])) {
            $data['emails'] = array($data['emails']);
        }
        if (count($data['emails']) > 0) {
            $emails = array_merge($emails, $data['emails']);
        }
    }
    
    $location = $data['location'];
    $address = $data['address'];
    $phone = $data['phone'];
    $description = $data['description'];
    $categories = $data['categories']; */


	if ($moreinfo) {
		$source = getHTML($moreinfo);
		$html = str_get_html($source);
		if ($html) {
			$yellowpageEmails = array_map(
				function($element) { return str_ireplace('mailto:', '', $element->href); },
				$html->find('.email-business')
			);

			$address = call_user_func( array($scrapeClass, 'get_address'), $html);
			$location = call_user_func( array($scrapeClass, 'get_location'), $html);
			$phone = call_user_func( array($scrapeClass, 'get_phone'), $html);
			if($location!=null && $phone!=null) {
				$description = call_user_func( array($scrapeClass, 'get_description'), $html);
				$categories = call_user_func( array($scrapeClass, 'get_categories'), $html);
			}

			$html->clear();
			unset($html);
		}

		if (isset($yellowpageEmails) && count($yellowpageEmails) > 0)
			$emails = array_merge($emails, $yellowpageEmails);
	}
}


function print_out($message) {
    if(!defined('AJAX_QUERY')) {
        echo $message;
    }
}