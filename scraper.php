<?php

require_once('simple_html_dom/simple_html_dom.php');
require_once('utils.php');

class Scraper {
    public static function run(
        $name,
        $scrapeClass,
        $startPage,
        $endPage,
        $location,
        $search,
        $url,
        $requireEmails = false
    ) {
        $csv = '';
        $csvFilename = "scraper-".$location."-".$search."-P".$startPage."-".$endPage;

        $leads = array();
        $domainArray = array();
        $scannedArray = array();

        Utils\print_out("<br>Scraping $name...\n<br>Search: $search\n<br>Location: $location\n<br>");

        $threads = array();
        for ($i = $startPage; $i <= $endPage; $i++) {
            $t = self::scrapePage(
                $scrapeClass,
                $i,
                $csv,
                $location,
                $search,
                $url,
                $leads,
                $scannedArray,
                $domainArray,
                $requireEmails
            );
            // $threads[] = $t;
        }

        Utils\print_out("\n");

        // wait for all threads
        /*foreach ($threads as $t) {
            $t->join();
        }
        echo "\n".print_r('THREADS FINALIZED!!', true)."\n";
        */
        
        Utils\print_out("\n<br>".count($leads)." valid leads found!");

        //if( defined('AJAX_QUERY') ) {
            header('Content-type: application/json');
            $json = json_encode($leads);
            echo $json;

            file_put_contents($csvFilename.".json", $json, LOCK_EX);
        //}

        if ($leads && count($leads) > 0) {
            // $csv = '"' . implode('","', array_keys(reset($leads))) . "\"\n" . $csv;
        }

        // file_put_contents($csvFilename.".csv", $csv, LOCK_EX);
    }

    private static function getEmptyLead() {
        $lead = array(
            'name' => null,
            'website' => null,
            'email' => null
        );

        return $lead;
    }

    private static function scrapePage(
        $scrapeClass,
        $i,
        &$csv,
        $location,
        $search,
        $url,
        &$leads,
        &$scannedArray,
        $domainArray,
        $requireEmails
    ) {
    	
    	Utils\print_out("Page ".$i);
    	$data = array(
    			'g' => $location,
    			'page' => $i,
    			'q' => $search
    	);
    	$entries = call_user_func(array($scrapeClass, 'scrapePage'), $url, $data);
        if($entries!==null) {
            foreach($entries as $entry) {
                if (self::parseEntry(
                        $scrapeClass,
                        $csv,
                        $entry,
                        $leads,
                        $scannedArray,
                        $domainArray,
                        $requireEmails
                        )) continue;
            }
        }
    	
        
        //start the thread...
        /*$thread = new ThreadScraper();
        $thread->setData(
            $scrapePage,
            $i,
            $csv,
            $location,
            $search,
            $url,
            $leads,
            $scannedArray,
            $scanModules,
            $requiredModules,
            $domainArray,
            $requireEmails
        );
        $thread->start();

        return $thread;*/
    }

    public static function parseEntry(
        $scrapeClass,
        &$csv,
        $entry,
        &$leads,
        &$scannedArray,
        $domainArray,
        $requireEmails
    ) {
        list($name, $link, $moreinfo) = $entry;
        $name = $name ? trim($name) : null;
        if( empty($name) ) {
            return false;
        }

        if (isset($scannedArray[$name]) || (!$link )) return false;

        
        $leads[$name] = self::getEmptyLead();
        // $scannedArray[$name] = self::getEmptyLead();

        $emails = array();
        $location = array();
        $address = '';
        $phone = '';
        $description = '';
        $categories = array();
        Utils\get_more_info_lead($emails, $address, $location, $phone, $description, $categories, $moreinfo, $scrapeClass);

        if  (!self::parseLink(
            $link,
            $domainArray,
            $emails,
            $leads,
            $name
        )) {
            unset($leads[$name]);
            return false;
        }

        if ($requireEmails && count($emails) <= 0) {
            unset($leads[$name]);
            return false;
        }
        
        // printEmails($emails);
        if(isset($location['lat'])) {
            $leads[$name]['latitude'] = $location['lat'];
            $leads[$name]['longitude'] = $location['long'];    
        } else {
            unset($leads[$name]);
            return false;
        }
        $leads[$name]['name'] = $name;
        $leads[$name]['email'] = (
        		$emails !== false && count($emails) > 0
        		? implode(", ", array_unique($emails))
        		: null
        		);
        
        $leads[$name]['description'] = $description;
        $leads[$name]['address'] = $address;
        $leads[$name]['phone'] = $phone;
        $leads[$name]['categories'] = $categories;
        
        Utils\print_out( "<pre>".print_r($leads[$name], true)."</pre>" );
        // $csv .= '"' . implode('","', $leads[$name]) . "\"\n";

        return true;
    }

    private static function parseLink(
        $link,
        $domainArray,
        &$emails,
        &$leads,
        $name
    ) {
        if ($link) {
            $domain = str_ireplace('www.', '', parse_url($link, PHP_URL_HOST));

            if (in_array($domain, (array)$domainArray)) return false;

            $domainArray[] = $domain;

            Utils\get_whois_emails($emails, $domain);

            $leads[$name]['website'] = $link;
        }

        return true;
    }
}