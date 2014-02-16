<?php

include('simple_html_dom/simple_html_dom.php');
include('phpwhois-4.2.2/whois.main.php');

$startPage = 2;
$endPage = 2;

$location = 'Financial District, New York, NY';
$search = 'Financial Advisors';
$url = 'http://www.yellowpages.com/financial-district-new-york-ny/financial-advisors';

$requireEmail = true;

$scanModules = array(
    'Google Analytics' => 'googleAnalytics',
    //'Shopify' => 'shopify',
    'Twitter Bootstrap' => 'bootstrap'
);

$leads = array();
$domainArray = array();
$csv = '';

$csvFilename = "csv/$search-leads_p$startPage-$endPage.csv";

echo "Scraping yellowpages.com...\nSearch: $search\nLocation: $location\n";

for ($i = $startPage; $i <= $endPage; $i++) {
    echo "\nPage $i";

    $data = array(
        'g' => $location,
        'page' => $i,
        'q' => $search
    );

    $entries = scrape_page($url, $data);

    foreach($entries as $entry) {
        list($name, $link, $moreinfo) = $entry;

        if (isset($leads[$name])) continue;

        echo "\n$name";

        $leads[$name] = getEmptyLead($scanModules);

        $emails = array();
        get_moreinfo_emails($emails, $moreinfo);

        if ($link) {
            $domain = str_ireplace('www.', '', parse_url($link, PHP_URL_HOST));

            if (in_array($domain, $domainArray)) continue;

            $domainArray[] = $domain;

            get_whois_emails($emails, $domain);

            $leads[$name]['Website'] = $link;

            scan_modules($scanModules, $link, $leads, $name);
        }

        if ($requireEmail && count($emails) == 0) {
            unset($leads[$name]);
        }

        printEmails($emails);

        $leads[$name]['Name'] = $name;
        $leads[$name]['Email Addresses'] = (
            $emails !== false && count($emails) > 0 
            ? implode(", ", array_unique($emails)) 
            : null
        );

        $csv .= '"' . implode('","', $leads[$name]) . "\"\n";
    }
}

echo "\n";

$csv = '"' . implode('","', array_keys(reset($leads))) . "\"\n" . $csv;

file_put_contents($csvFilename, $csv);

function getEmptyLead($scanModules) {
    $lead = array(
        'Name' => null,
        'Website' => null,
        'Email Addresses' => null
    );

    foreach (array_keys($scanModules) as $module) {
        $lead[$module] = null;
    }

    return $lead;
}

function scrape_page($url, $data) {
    $html = str_get_html(getHTML($url, $data));

    $entries = array();
    foreach($html->find('.result-container') as $listing) { 
        $name = $listing->find('div.business-name-container, div.srp-business-name', 0);
        $link = $listing->find('div.info-business-additional a.track-visit-website', 0);
        $moreinfo = $listing->find('a.track-more-info', 0);
        $entries[] = array(
            $name ? trim(htmlspecialchars_decode($name->plaintext)) : null,
            $link ? $link->href : null,
            $moreinfo ? 'http://www.yellowpages.com' . $moreinfo->href : null
        );
    }

    $html->clear();
    unset($html);

    return $entries;
}

function get_whois_emails(&$emails, $domain) {
    $whois = new Whois();
    $result = @$whois->Lookup($domain);
    $whoisemails = array_find_emails('email', $result['rawdata']);
    if ($whoisemails && count($whoisemails) > 0)
        $emails = array_merge($emails, $whoisemails);
}

function get_moreinfo_emails(&$emails, $moreinfo) {
    if ($moreinfo) {
        $source = getHTML($moreinfo);
        $html = str_get_html($source);
        $yellowpageEmails = array_map(
            function($element) { return str_ireplace('mailto:', '', $element->href); },
            $html->find('.email-business')
        );

        $html->clear();
        unset($html);

        if ($yellowpageEmails && count($yellowpageEmails) > 0)
            $emails = array_merge($emails, $yellowpageEmails);
    }
}

function scan_modules($scanModules, $link, &$leads, $name) {
    $website = getHTML($link);

    echo "\n" . str_pad("", (25 * count($scanModules)) + 1, '-') . "\n";
    foreach ($scanModules as $module => $callback) {
        $value = $callback($website);
        echo str_pad("| $module: $value", 25);
        $leads[$name][$module] = $value;
    }
    echo "|\n" . str_pad("", (25 * count($scanModules)) + 1, '-');
}

function printEmails($emails) {
    if ($emails && count($emails) > 0) {
        $emailString = '| Email: ' . implode(', ', $emails) . ' ';
        echo "\n" . str_pad("", strlen($emailString) + 1, '-') . "\n";
        echo $emailString;
        echo "|\n" . str_pad("", strlen($emailString) + 1, '-');
    }
}

function array_find_emails($needle, $haystack)
{
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

function getHTML($url, $data = null) {
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url . ($data === null ? '' : '?' . http_build_query($data)));
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    if (!$result = curl_exec($curl)) {
        //echo curl_error($curl) . "\n";
    }

    curl_close($curl);

    return $result;
}

function googleAnalytics($website) {
    $ga = 'No';

    if (stripos($website, 'analytics.js') !== false 
        || stripos($website, 'ga.js') !== false
    ) {
        $ga = 'Yes';
    }

    return $ga;
}

function shopify($website) {
    $shopify = 'No';

    if (stripos($website, 'cdn.shopify') !== false) {
        $shopify = 'Yes';
    }

    return $shopify;
}

function bootstrap($website) {
    $bootstrap = 'No';

    if (stripos($website, 'bootstrap.min.css') !== false 
        || stripos($website, 'bootstrap.css') !== false
    ) {
        $bootstrap = 'Yes';
    }

    return $bootstrap;
}

?>
