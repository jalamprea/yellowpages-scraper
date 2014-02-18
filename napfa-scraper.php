<?php

require_once('scraper.php');

class NapfaScraper {
    public static function run() {
        $startPage = 1;
        $endPage = 25;

        $location = 'Financial District, New York, NY';
        $search = 'Financial Advisors';
        $url = 'http://findanadvisor.napfa.org/Home.aspx/Search';

        $scanModules = array(
            'Google Analytics' => 'googleAnalytics',
            //'Shopify' => 'shopify',
            'Twitter Bootstrap' => 'bootstrap'
        );

        $requireEmail = true;

        Scraper::run($startPage, $endPage, $location, $search, $url, $scanModules, $requireEmail);
    }
}

?>
