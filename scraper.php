<?php

require_once('simple_html_dom/simple_html_dom.php');
require_once('utils.php');
require_once('scan_modules.php');

class Scraper {
    public static function run(
        $name,
        $scrapePage,
        $startPage,
        $endPage,
        $location,
        $search,
        $url,
        $scanModules,
        $requiredModules,
        $requireEmails = false
    ) {
        $csv = '';
        $csvFilename = "csv/$search-leads_p$startPage-$endPage.csv";

        $leads = array();
        $domainArray = array();
        $scannedArray = array();

        echo "Scraping $name...\nSearch: $search\nLocation: $location\n";

        for ($i = $startPage; $i <= $endPage; $i++) {
            self::scrapePage(
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
        }

        echo "\n";

        if ($leads && count($leads) > 0) {
            $csv = '"' . implode('","', array_keys(reset($leads))) . "\"\n" . $csv;
        }

        file_put_contents($csvFilename, $csv);
    }

    private static function getEmptyLead($scanModules) {
        $lead = array(
            'Name' => null,
            'Website' => null,
            'Email Addresses' => null
        );

        foreach (array_keys($scanModules) as $module) {
            $lead[$module] = null;
        }

        return $lead;
    }

    private static function scrapePage(
        $scrapePage,
        $i,
        &$csv,
        $location,
        $search,
        $url,
        &$leads,
        &$scannedArray,
        $scanModules,
        $requiredModules,
        $domainArray,
        $requireEmails
    ) {
        echo "\nPage $i";

        $data = array(
            'g' => $location,
            'page' => $i,
            'q' => $search
        );

        $entries = call_user_func($scrapePage, $url, $data);

        foreach($entries as $entry) {
            if (self::parseEntry(
                $csv,
                $entry,
                $leads,
                $scannedArray,
                $domainArray,
                $scanModules,
                $requiredModules,
                $requireEmails
            )) continue;
        }
    }

    private static function parseEntry(
        &$csv,
        $entry,
        &$leads,
        &$scannedArray,
        $domainArray,
        $scanModules,
        $requiredModules,
        $requireEmails
    ) {
        list($name, $link, $moreinfo) = $entry;

        if (isset($scannedArray[$name]) 
            || (!$link 
            && $requiredModules && count($requiredModules > 0))) return false;

        echo "\n$name";

        $leads[$name] = self::getEmptyLead($scanModules);
        $scannedArray[$name] = self::getEmptyLead($scanModules);

        $emails = array();
        get_moreinfo_emails($emails, $moreinfo);

        if  (!self::parseLink(
            $link,
            $scanModules,
            $requiredModules,
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

        printEmails($emails);

        $leads[$name]['Name'] = $name;
        $leads[$name]['Email Addresses'] = (
            $emails !== false && count($emails) > 0 
            ? implode(", ", array_unique($emails)) 
            : null
        );

        $csv .= '"' . implode('","', $leads[$name]) . "\"\n";

        return true;
    }

    private static function parseLink(
        $link,
        $scanModules,
        $requiredModules,
        $domainArray,
        &$emails,
        &$leads,
        $name
    ) {
        if ($link) {
            $domain = str_ireplace('www.', '', parse_url($link, PHP_URL_HOST));

            if (in_array($domain, $domainArray)) return false;

            $domainArray[] = $domain;

            get_whois_emails($emails, $domain);

            $leads[$name]['Website'] = $link;

            scan_modules($scanModules, $link, $leads, $name);

            if ($requiredModules && count($requiredModules) > 0) {
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
            }
        }

        return true;
    }
}

?>
