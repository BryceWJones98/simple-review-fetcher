<?php

function fetch($config)
{
    $apiKey = $config->directory_settings->google_places->api_key;
    $placeID = $config->directory_settings->google_places->place_id;

    if (
        $config->datastore_settings->medium == 'json' &&
        isset($config->datastore_settings->path)
    ) {
        $relativeDatastorePath = "../../" . $config->datastore_settings->path;

        if (file_exists($relativeDatastorePath)) {
            //load existing data from datastore file
            $existingData = json_decode(
                file_get_contents($relativeDatastorePath),
            );

            if (
                time() >=
                $config->datastore_settings->update_frequency +
                $existingData->last_modified
            ) {
                //pull latest data from google api, save it, and return updated dataset
                $newData = getGoogleReviews($apiKey, $placeID);

                return saveReviewsJSON(
                    $newData,
                    $relativeDatastorePath,
                    $existingData->reviews->google_reviews,
                );
            } else {
                //return existing data from datastore file
                return $existingData;
            }
        } else {
            //get initial data from google api, save it, and return it
            $newData = getGoogleReviews($apiKey, $placeID);

            return saveReviewsJSON($newData, $relativeDatastorePath);
        }
    }
    return false;
}

function getGoogleReviews($apiKey, $placeID)
{
    return json_decode(
        file_get_contents(
            "https://maps.googleapis.com/maps/api/place/details/json?key={$apiKey}&place_id={$placeID}&fields=review",
        ),
    )->result->reviews;
}

function saveReviewsJSON($reviews, $path, $existingReviews = null)
{
    $saveData = new stdClass();
    $saveData->reviews = new stdClass();
    $saveData->reviews->google_reviews = new stdClass();

    if (isset($existingReviews)) {
        //adds existing saved reviews to dataset that should be saved
        foreach ($existingReviews as $key => $existingReview) {
            //when the review is saved, it's hash is used as the key.
            $saveData->reviews->google_reviews->{$key} = $existingReview;
        }
    }

    foreach ($reviews as $review) {
        $reviewHash = md5(json_encode($review));
        $saveData->reviews->google_reviews->{$reviewHash} = $review;
    }

    $saveData->last_modified = time();

    $fp = fopen($path, 'w');
    fwrite($fp, json_encode($saveData, JSON_PRETTY_PRINT));
    fclose($fp);

    return $saveData;
}
