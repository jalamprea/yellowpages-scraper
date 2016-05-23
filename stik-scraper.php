<?php

require_once('scraper.php');

class StikScraper {
    public static function run() {
        $startPage = 1;
        //$startPage = 169;
        $endPage = 1;

        /*
        $location = 'Financial District, New York, NY';
        $search = 'Financial Advisors';
        $url = 'http://www.yellowpages.com/financial-district-new-york-ny/financial-advisors';
         */
        /*
        $location = 'New York, NY';
        $search = 'Jewelry Stores';
         */
        $name = 'stik.com';
        $url = 'http://www.stik.com/home-services-electrical';
        $search = 'home-services-electrical';
        $location = 'USA';

        $scanModules = array(
            //'Google Analytics' => 'googleAnalytics',
            //'Shopify' => 'shopify'
            //'Twitter Bootstrap' => 'bootstrap'
        );

        $requiredModules = array(
            //'Shopify'
        );

        $requireEmails = false;

        Scraper::run(
            $name,
            array('StikScraper', 'scrapePage'),
            $startPage,
            $endPage,
            $location,
            $search,
            $url,
            $scanModules,
            $requiredModules,
            $requireEmails
        );
    }

    public static function scrapePage($url, $data) {
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
}

?>
