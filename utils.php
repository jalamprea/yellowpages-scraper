<?php

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
    $whois = new Whois();
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
                'isRelevantEmail'
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

function get_more_info_lead(&$emails, &$address, &$location, &$phone, &$description, $moreinfo) {
	if ($moreinfo) {
		$source = getHTML($moreinfo);
		$html = str_get_html($source);
		if ($html) {
			$yellowpageEmails = array_map(
				function($element) { return str_ireplace('mailto:', '', $element->href); },
				$html->find('.email-business')
			);

			$address = YellowpagesScraper::get_address($html);
			$location = YellowpagesScraper::get_location($html);
			$phone = YellowpagesScraper::get_phone($html);
			$description = YellowpagesScraper::get_description($html);

			$html->clear();
			unset($html);
		}

		if (isset($yellowpageEmails) && count($yellowpageEmails) > 0)
			$emails = array_merge($emails, $yellowpageEmails);
	}
}