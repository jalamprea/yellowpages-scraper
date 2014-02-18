<?php

include('utils.php');
include('geocoding.php');

$address = 'New York';
$geocode = getGeocode($address);
$lat = $geocode->results[0]->geometry->location->lat;
$lng = $geocode->results[0]->geometry->location->lng;

echo getHTML('http://findanadvisor.napfa.org/Home.aspx', array(
    'autosearch' => '1',
    'address' => $address,
    'latitude' => $lat,
    'longitude' => $lng
), false, true);
