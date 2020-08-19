<?php

$config = json_decode(
    file_get_contents('../../config.json')
);

print_r(fetch($config));

function fetch($config){

    $apiKey = $config->directory_settings->google_places->api_key;
    $placeID = $config->directory_settings->google_places->place_id;

    if($config->datastore_settings->medium == 'json' && isset($config->datastore_settings->path)){

        $relativeDatastorePath = "../../" . $config->datastore_settings->path;

        if(file_exists($relativeDatastorePath)){
            //load existing data from datastore file
            $existingData = json_decode(
                file_get_contents($relativeDatastorePath)
            );

            if(time() >= $config->datastore_settings->update_frequency + $existingData->last_modified){
                //pull latest data from google api, save it, and return it
                $newData = getGoogleReviews($apiKey, $placeID);

                saveReviewsJSON($newData, $relativeDatastorePath);

                return $newData;
            } else{
                //return existing data from datastore file
                return $existingData;
            }
        } else {
            //get initial data from google api, save it, and return it

            $newData = getGoogleReviews($apiKey, $placeID);

            saveReviewsJSON($newData, $relativeDatastorePath);
        }

    }

    return false;
}

function getGoogleReviews($apiKey, $placeID){

    return json_decode(
        file_get_contents("https://maps.googleapis.com/maps/api/place/details/json?key={$apiKey}&place_id={$placeID}&fields=review")
    )->result->reviews;

}

function saveReviewsJSON($reviews, $path, $existingReviews = null){

    $saveData = new stdClass();
    $saveData->reviews = new stdClass();
    $saveData->reviews->google_reviews = new stdClass();

    //TODO: Feed list of previously saved reviews (if they exist) to this function so the new reviews can be appended to the file. Compare hashes to avoid duplication.

    $saveData = new stdClass();

    foreach($reviews as $review){
        $reviewHash = md5(json_encode($review));

        $saveData->reviews->google_reviews[$reviewHash] = $review;
    }

    $saveData->last_modified = time();

    $fp = fopen($path, 'w');
    fwrite($fp, json_encode($saveData, JSON_PRETTY_PRINT));
    fclose($fp);

    return;
}