<?php

require_once('scraper.php');
require_once('simple_html_dom/simple_html_dom.php');
require_once('utils.php');

class YellowpagesScraper {
    public static function run() {
        $startPage = 1;
        //$startPage = 169;
        $endPage = 198;

        /*
        $location = 'Financial District, New York, NY';
        $search = 'Financial Advisors';
        $url = 'http://www.yellowpages.com/financial-district-new-york-ny/financial-advisors';
         */
        $name     = 'yellowpages.com';
        $location = 'New York, NY';
        $search   = 'Jewelry Stores';
        $url      = 'http://www.yellowpages.com/new-york-ny/jewelry-stores';

        $scanModules = array(
            //'Google Analytics' => 'googleAnalytics',
            'Shopify' => 'shopify'
            //'Twitter Bootstrap' => 'bootstrap'
        );

        $requiredModules = array(
            //'Shopify'
        );

        $requireEmails = false;

        Scraper::run(
            $name,
            array('YellowpagesScraper', 'scrapePage'),
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
        foreach($html->find('.result') as $listing) { 
            $name = $listing->find('a.business-name span', 0);
            $link = $listing->find('ul.links a.track-visit-website', 0);
            $moreinfo = $listing->find('ul.links a.track-more-info', 0);
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
