<?php

include('simple_html_dom/simple_html_dom.php');
include('phpwhois-4.2.2/whois.main.php');

$startPage = 1;
$endPage = 50;

$scanModules = array(
    'Google Analytics' => 'googleAnalytics',
    'Shopify' => 'shopify',
    'Twitter Bootstrap' => 'bootstrap'
);

$leads = array();
$csv = '';

$csvFilename = 'leads-with-emails.csv';

echo "Beginning yellowpages.com listing scrape\n";

for ($i = $startPage; $i <= $endPage; $i++) {
    echo "\nPage $i";

    $data = array(
        'g' => 'East Williamsburg, Brooklyn, NY',
        'page' => $i,
        'q' => 'accountant'
    );

    $url = 'http://www.yellowpages.com/east-williamsburg-brooklyn-ny/accountant';

    $html = str_get_html(getHTML($url, $data));

    $links = array();
    foreach($html->find('div.info-business-additional a.track-visit-website') as $link) {
        $links[] = $link->href;
    }

    $html->clear();
    unset($html);

    foreach($links as $link) {
        $domain = str_ireplace('www.', '', parse_url($link, PHP_URL_HOST));

        if (isset($leads[$domain])) continue;
        echo "\n$link";

        $leads[$domain] = array();

        $whois = new Whois();
        $result = @$whois->Lookup($domain);
        $emails = array_find_emails('email', $result['rawdata']);

        if ($emails === false) {
            echo "...No WHOIS Email";
            continue;
        }

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

        /*
        foreach($scriptSources as $key => $source) {
            if (stripos($source, 'analytics.js') !== false 
                || stripos($source, 'ga.js') !== false
            ) {
                $ga = 'Implemented';
            }
        }
         */

        $leads[$domain] = array(
            'Website' => $link,
            'Email Addresses' => implode(", ", array_unique($emails))
        );

        foreach ($scanModules as $name => $callback) {
            $value = $callback($website);
            echo "...$value";
            $leads[$domain][$name] = $value;
        }

        $csv .= '"' . implode('","', $leads[$domain]) . "\"\n";
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
                        && stripos($word, 'no.valid.email@worldnic.com') === false
                        && stripos($word, 'customerservice@networksolutions.com') === false
                        && stripos($word, 'contact@myprivateregistration.com') === false
                        && stripos($word, '@networksolutionsprivateregistration.com') === false
                        && stripos($word, 'admin@internationaladmin.com') === false
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
