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
        $csvFilename = "csv/$search-leads_p$startPage-$endPage.csv";

        $leads = array();
        $domainArray = array();
        $scannedArray = array();

        echo "<br>Scraping $name...\n<br>Search: $search\n<br>Location: $location\n<br>";

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
            $threads[] = $t;
        }

        echo "\n";

        // wait for all threads
        /*foreach ($threads as $t) {
            $t->join();
        }
        echo "\n".print_r('THREADS FINALIZED!!', true)."\n";
        */
        
        echo "\n<br>".count($leads)." valid leads found!";

        if ($leads && count($leads) > 0) {
            //$csv = '"' . implode('","', array_keys(reset($leads))) . "\"\n" . $csv;
        }

        // file_put_contents($csvFilename, $csv);
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
    	
    	echo "Page $i";
    	$data = array(
    			'g' => $location,
    			'page' => $i,
    			'q' => $search
    	);
    	$entries = call_user_func(array($scrapeClass, 'scrapePage'), $url, $data);
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
        //get_moreinfo_emails($emails, $moreinfo);
        Utils\get_more_info_lead($emails, $address, $location, $phone, $description, $moreinfo, $scrapeClass);

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

        $leads[$name]['name'] = $name;
        $leads[$name]['email'] = (
            $emails !== false && count($emails) > 0 
            ? implode(", ", array_unique($emails)) 
            : null
        );

        $leads[$name]['description'] = $description;
        $leads[$name]['address'] = $address;
        $leads[$name]['phone'] = $phone;
        if(isset($location['lat'])) {
            $leads[$name]['latitude'] = $location['lat'];
            $leads[$name]['longitude'] = $location['long'];    
        } else {
            unset($leads[$name]);
            return false;
        }
        
        echo( "<pre>".print_r($leads[$name], true)."</pre>" );
        //$csv .= '"' . implode('","', $leads[$name]) . "\"\n";

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

            //scan_modules($scanModules, $link, $leads, $name);

            /*if ($requiredModules && count($requiredModules) > 0) {
                $requiredValues = array_map(
                    function($module) use ($leads, $name) {
                        return $leads[$name][$module];
                    }, $requiredModules
                );

                $requiredValues = array_filter(
                    $requiredValues,
                    function($module) {
                        return $module !== 'No';
                    }
                );

                if (count($requiredModules) !== count($requiredValues)) {
                    return false;
                }
            }*/
        }

        return true;
    }
}