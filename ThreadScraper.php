<?php

class ThreadScraper /*extends Thread*/ {
	public $data;


	public function setData(
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
        $requireEmails) {

		$this->scrapePage = $scrapePage;
        $this->i = $i;
        $this->csv = $csv;
        $this->location = $location;
        $this->search = $search;
        $this->url = $url;
        $this->leads = $leads;
        $this->scannedArray = $scannedArray;
        $this->scanModules = $scanModules;
        $this->requiredModules = $requiredModules;
        $this->domainArray = $domainArray;
        $this->requireEmails = $requireEmails;
	}

	public function run() {
		echo "\nPage ".$this->i." ";

		$data = array(
            'g' => $this->location,
            'page' => $this->i,
            'q' => $this->search
        );


		$staticCallback = $this->scrapePage[0].'::'.$this->scrapePage[1];
		// die( "<pre>".print_r($this->scanModules, true)."</pre>" );
        $entries = call_user_func($staticCallback, $this->url, $this->data);

        foreach($entries as $entry) {
            if (Scraper::parseEntry(
                $this->csv,
                $entry,
                $this->leads,
                $this->scannedArray,
                $this->domainArray,
                $this->scanModules,
                $this->requiredModules,
                $this->requireEmails
            )) continue;
        }
    }

    public function start() {
    	$this->run();
    }
}