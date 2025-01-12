<?php

if (strtolower(php_sapi_name()) !== 'cli') {
    throw new \Exception('Please run this file from command line.');
    exit();
}

require 'config.php';
require 'vendor/autoload.php';

$Db = new \PMTL\Libraries\Db();
$dbh = $Db->connect();

set_time_limit(3000000);
ini_set('memory_limit', '2048M');

echo 'This script will retrieve place details using Nominatim reverse geocoding and update the DB.' . PHP_EOL
    . 'Are you sure to continue? [y/n]';
$confirmation = strtolower(trim(fgets(STDIN)));
if ($confirmation !== 'y') {
    echo 'Cancelled.';
    exit(1);
}

$sql = 'SELECT `visit_id`, `topCandidate_placeId`, `topCandidate_placeLocation_latLng`, COUNT(`topCandidate_placeId`) AS `countPlaceId`,
`google_places`.`place_name`, `google_places`.`last_update`
FROM `visit`
LEFT JOIN `google_places` ON `visit`.`topCandidate_placeId` = `google_places`.`place_id`
GROUP BY `topCandidate_placeId`
ORDER BY `countPlaceId` DESC
LIMIT 1000 OFFSET 0';
$Sth = $dbh->prepare($sql);
unset($sql);
$Sth->execute();
$result = $Sth->fetchAll();
$Sth->closeCursor();
unset($Sth);

if ($result) {
    $updated = 0;
    foreach ($result as $row) {
        echo $row->topCandidate_placeId;
        echo ' (' . $row->countPlaceId . ')';
        echo PHP_EOL;

        // Extract latitude and longitude for reverse geocoding
        $latLng = $row->topCandidate_placeLocation_latLng;
        $latLng = preg_replace('/[^\d\.\-\,]/', '', $latLng); // Clean up coordinates
        [$latitude, $longitude] = explode(',', $latLng);

        // Get place details using Nominatim
        $placeDetails = nominatimReverseGeocode($latitude, $longitude);
        $placeObj = json_decode($placeDetails);
        $placeName = null;

        if (isset($placeObj->display_name)) {
            $placeName = trim($placeObj->display_name);
            echo '  "' . $placeName . '"';
        } else {
            echo '  Error retrieving place details.' . PHP_EOL;
        }

        // Save data to the database
        $sql = 'INSERT INTO `google_places` (`place_id`, `place_name`, `last_update`) VALUES (:place_id, :place_name, :last_update)
                ON DUPLICATE KEY UPDATE `place_name` = :place_name';
        $Sth = $dbh->prepare($sql);
        unset($sql);
        $Sth->bindValue(':place_id', trim($row->topCandidate_placeId));
        $Sth->bindValue(':place_name', $placeName);
        $Sth->bindValue(':last_update', date('Y-m-d H:i:s'));
        $Sth->execute();
        $insertId = $dbh->lastInsertId();
        $Sth->closeCursor();
        unset($Sth);

        if (false !== $insertId) {
            ++$updated;
            echo '  :: inserted/updated';
            if (is_null($placeName)) {
                echo ' (as `NULL`)';
            }
        }

        // Delay for rate limiting
        sleep(1);

        echo PHP_EOL . PHP_EOL;
        unset($placeDetails, $placeName, $placeObj, $latitude, $longitude);
    }
    echo 'Total ' . count($result) . ' rows, inserted/updated ' . $updated . ' rows.' . PHP_EOL;
    unset($updated);
} else {
    echo 'Total 0 rows found. Nothing to retrieve.' . PHP_EOL;
}
unset($result);

$Db->disconnect();
unset($Db, $dbh);

/**
 * Reverse geocoding with Nominatim.
 *
 * @link https://nominatim.org/release-docs/develop/api/Reverse/ Reference/documentation.
 * @param float $latitude
 * @param float $longitude
 * @return object
 */
function nominatimReverseGeocode(float $latitude, float $longitude)
{
    $url = 'https://nominatim.openstreetmap.org/reverse?format=json&lat=' . $latitude . '&lon=' . $longitude;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'YourAppName/1.0 (your_email@example.com)'); // Replace with your app info
    $response = curl_exec($ch);
    curl_close($ch);
    unset($ch);

    return $response;
}
