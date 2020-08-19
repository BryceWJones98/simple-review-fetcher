<?php

include '../../src/php/fetcher.php';

$config = json_decode(file_get_contents('../../config.json'));

$reviews = fetch($config);

echo "<h1>Our Reviews</h1>";

foreach ($reviews->reviews->google_reviews as $review){

    echo "
        <h2>Author:</h2>
        <p>{$review->author_name} - <a href=\"{$review->author_url}\" target=\"_blank\">Link to Profile</a></p>
        
        <h2>Review Language:</h2>
        <p>{$review->language}</p>
        
        <h2>Profile Photo:</h2>
        <div><img src=\"{$review->profile_photo_url}\" alt=\"{$review->author_name} profile photo\"></div>
        
        <h2>Rating:</h2>
        <p>{$review->rating}</p>
        
        <h2>Review Text:</h2>
        <p>{$review->text}</p>
    ";

}
