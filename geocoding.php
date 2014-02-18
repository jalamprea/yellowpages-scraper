<?php

function getGeocode($address) {
    $data = array(
        'address' => $address,
        'sensor' => 'false'
    );

    $url = 'http://maps.googleapis.com/maps/api/geocode/json?' . http_build_query($data);

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($curl);

    curl_close($curl);

    return json_decode($result);
}

?>
