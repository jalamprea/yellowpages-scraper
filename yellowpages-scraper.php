<?php

require_once('scraper.php');

class YellowpagesScraper {
    public static function run() {
        $startPage = 1;
        $endPage = 1;

        /*
        $location = 'Financial District, New York, NY';
        $search = 'Financial Advisors';
        $url = 'http://www.yellowpages.com/financial-district-new-york-ny/financial-advisors';
         */
        $location = 'East Williamsburg, Brooklyn, NY';
        $search = 'Clothing Stores';
        $url = 'http://www.yellowpages.com/east-williamsburg-brooklyn-ny/clothing-stores';

        $scanModules = array(
            'Google Analytics' => 'googleAnalytics',
            'Shopify' => 'shopify',
            'Twitter Bootstrap' => 'bootstrap'
        );

        $requiredModules = array(
            'Shopify'
        );

        $requireEmails = false;

        Scraper::run($startPage, $endPage, $location, $search, $url, $scanModules, $requiredModules, $requireEmails);
    }
}

?>
