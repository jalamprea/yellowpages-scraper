<?php
require_once('utils.php');

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
