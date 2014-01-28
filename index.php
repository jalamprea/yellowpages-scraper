<?php

include('simple_html_dom/simple_html_dom.php');
include('phpwhois-4.2.2/whois.main.php');

$startPage = 1;
$endPage = 50;

$leads = array();
$csv = '';

$csvFilename = 'leads-with-emails.csv';

echo "Beginning yellowpages.com listing scrape\n";

for ($i = $startPage; $i <= $endPage; $i++) {
    echo "Page $i\n";

    $data = array(
        'g' => 'East Williamsburg, Brooklyn, NY',
        'page' => $i,
        'q' => 'clothing stores'
    );

    $url = 'http://www.yellowpages.com/east-williamsburg-brooklyn-ny/clothing-stores';

    $html = str_get_html(getHTML($url, $data));

    $links = array();
    foreach($html->find('div.info-business-additional a.track-visit-website') as $link) {
        $links[] = $link->href;
    }

    $html->clear();
    unset($html);

    foreach($links as $link) {
        if (isset($leads[$link])) continue;
        echo "$link\n";

        $whois = new Whois();
        $result = @$whois->Lookup(str_ireplace('www.', '', parse_url($link, PHP_URL_HOST)), false);
        $emails = array_find_emails('email', $result['rawdata']);

        if ($emails === false) continue;

        $website = getHTML($link);
        /*
        $websiteHTML = str_get_html($website);

        if (!$websiteHTML) continue;

        $scripts = $websiteHTML->find('script');
        $scriptSources = array();
        foreach($scripts as $script) {
            $source = getHTML($script->src);
            $scriptSources[$script->src] = $source;
        }

        $scriptSources = array_filter($scriptSources);
         */

        $ga = 'No';
        $shopify = 'No';
        $bootstrap = 'No';

        /*
        foreach($scriptSources as $key => $source) {
            if (stripos($source, 'analytics.js') !== false 
                || stripos($source, 'ga.js') !== false
            ) {
                $ga = 'Implemented';
            }
        }
         */

        if (stripos($website, 'analytics.js') !== false 
            || stripos($website, 'ga.js') !== false
        ) {
            $ga = 'Yes';
        }

        if (stripos($website, 'cdn.shopify') !== false) {
            $shopify = 'Yes';
        }

        if (stripos($website, 'bootstrap.min.css') !== false 
            || stripos($website, 'bootstrap.css') !== false
        ) {
            $bootstrap = 'Yes';
        }

        $lead = array(
            'Website' => $link,
            'Email Addresses' => implode(", ", array_unique($emails)),
            'Google Analytics' => $ga,
            'Shopify' => $shopify,
            'Twitter Bootstrap' => $bootstrap
        );

        $leads[$link] = $lead;

        $csv .= '"' . implode('","', $lead) . "\"\n";
    }
}

echo "\n";

$csv = '"' . implode('","', array_keys(reset($leads))) . "\"\n" . $csv;

file_put_contents($csvFilename, $csv);

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
                function($word) {
                    return $word !== false 
                        && stripos($word, 'host') === false
                        && stripos($word, 'whois') === false
                        && stripos($word, 'domain') === false
                        && stripos($word, 'dns') === false
                        && stripos($word, 'abuse') === false;
                }
            );

            $array = array_merge($array, $emails);
        }
    }

    return count($array) > 0 ? $array : false;
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

?>
