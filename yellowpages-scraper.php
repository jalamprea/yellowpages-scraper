<?php

require_once('scraper.php');
require_once('simple_html_dom/simple_html_dom.php');
require_once('utils.php');

class YellowpagesScraper {
    public static function run() {
        $startPage = isset($_REQUEST['from']) ? $_REQUEST['from'] : 169;
        $endPage = isset($_REQUEST['to']) ? $_REQUEST['to'] : 169;

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
        echo " <br>".print_r($url, true)."\n<br>";

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
        
		echo "Possible leads found: ".count($entries);
        
		return $entries;
    }
    
    
    
    /**
     * Find the street address in a HTML DOM Node
     * extracted from the more_info page of the lead.
     *
     * @param simple_html_dom_node $rootNode
     */
    public static function get_address($rootNode) {
    	$st = $rootNode->find('.street-address');
    	$cs = $rootNode->find('.city-state');
    
    	if( !empty($st) && !empty($cs) ) {
    		return $st[0]->innertext.' '.$cs[0]->innertext;
    	}
    
    	return null;
    }
    
    
    /**
     * Find and extract the Latitude and Longitude of the map
     *
     * @param simple_html_dom_node $rootNode
     * @return array(lat, long)[] | NULL
     */
    public static function get_location($rootNode) {
    	$locationData = $rootNode->find('#mip-mini-map');
    	if( !empty($locationData) ) {
    		return array(
    				'lat'=>$locationData[0]->getAttribute('data-latitude'),
    				'long'=>$locationData[0]->getAttribute('data-longitude')
    		);
    	}
    
    	return null;
    }
    
    
    /**
     *
     * @param simple_html_dom_node $rootNode
     * @return String phone if it's found, NULL if not.
     */
    public static function get_phone($rootNode) {
    	$phone = $rootNode->find('.phone');
    	if(!empty($phone)) {
    		return $phone[0]->innertext;
    	}
    
    	return null;
    }
    
    
    /**
     *
     * @param simple_html_dom_node $rootNode
     * @return String description if it's found. NULL if not.
     */
    public static function get_description($rootNode) {
    	$details = $rootNode->find('#business-details');
    	if( !empty($details) ) {
    		$descriptions = $details[0]->find('.description');
    		foreach ($descriptions as $desc_node) {
    			if( count($desc_node->children())===0 ) {
    				return $desc_node->innertext;
    			} else {
    				if( $desc_node->children(0)->tag!=='a' ) {
    					return strip_tags($desc_node->innertext);
    				}
    			}
    		}
    	}
    
    	return null;
    }
}