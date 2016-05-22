<?php

require_once('scraper.php');
require_once('simple_html_dom/simple_html_dom.php');
require_once('utils.php');

class YellowpagesScraper {
    public static function run() {
        $startPage = 1;
        //$startPage = 169;
        $endPage = 1;

        /*
        $location = 'Financial District, New York, NY';
        $search = 'Financial Advisors';
        $url = 'http://www.yellowpages.com/financial-district-new-york-ny/financial-advisors';
         */
        $name     = 'yellowpages.com';
        $location = 'New York, NY';
        $search   = 'electricians';
        $url      = 'http://www.yellowpages.com/new-york-ny/electricians/';

        $scanModules = array( );

        $requiredModules = array( );

        $requireEmails = true;

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
        echo " ".print_r($url, true)."\n";

        $html = str_get_html(getHTML($url, $data));

        $entries = array();
        $res = $html->find('.result');
        // $res = $html->find('#listings');

        if( empty($res) ) {
            $res = $html->find('.search-results');
        }
        foreach($res as $listing) {
            $name = $listing->find('a.business-name', 0);
            if($name)
                $name = strip_tags($name->innertext);
            else {
                unset($listing);
                continue;
            }
            
            $link = $listing->find('.links a.track-visit-website', 0);
            $link = ($link && $link->href) ? $link : $listing->find('.links a.website-link', 0);
            
            $moreinfo = $listing->find('.links a.track-more-info', 0);
            $moreinfo = $moreinfo ? $moreinfo : $listing->find('.links a.more-info-link', 0);

            if($link && $link->href) {
                $entries[] = array(
                    $name ? trim(htmlspecialchars_decode($name)) : null,
                    $link ? $link->href : null,
                    $moreinfo ? 'http://www.yellowpages.com' . $moreinfo->href : null
                );
            }
        }

        $html->clear();
        unset($html);

        return $entries;
    }
}

?>
