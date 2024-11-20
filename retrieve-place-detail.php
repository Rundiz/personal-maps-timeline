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
ini_set('memory_limit','2048M');


echo 'This will be retrieve place details from Google API and update to the DB. It may cost your money.' . PHP_EOL
    . 'It will be retrieve 1000 places per request.' . PHP_EOL
    . 'Are you sure to continue? [y/n]';
$confirmation = strtolower(trim(fgets(STDIN)));
if ($confirmation !=='y') {
   // The user did not say 'y'.
   echo 'Cancelled.';
   exit(1);
}


$sql = 'SELECT `visit_id`, `topCandidate_placeId`, `topCandidate_placeLocation_latLng`, COUNT(`topCandidate_placeId`) AS `countPlaceId`,
`google_places`.`place_name`, `google_places`.`last_update`
FROM `visit`
LEFT JOIN `google_places` ON `visit`.`topCandidate_placeId` = `google_places`.`place_id`
WHERE (`place_name` IS NULL AND `last_update` IS NULL)
    OR (`place_name` IS NULL AND `last_update` <= NOW() - INTERVAL 1 YEAR)
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
        $latLngForURL = preg_replace('/(,\s{1,})/', ',', $row->topCandidate_placeLocation_latLng);
        $latLngForURL = preg_replace('/[^\d\.\-\,]/', '', $latLngForURL);
        echo '  https://www.google.com/maps/search/?api=1&query=' . rawurlencode($latLngForURL) . '&query_place_id=' . rawurlencode($row->topCandidate_placeId);
        echo PHP_EOL;

        $place = curlGetPlaceDetail($row->topCandidate_placeId);
        $placeObj = json_decode($place);
        $placeName = null;
        if (isset($placeObj->displayName->text)) {
            $placeName = trim($placeObj->displayName->text);
            echo '  "' . $placeName . '"';
        } else {
            if (isset($placeObj->error->code)) {
                if (400 === intval($placeObj->error->code)) {
                    echo '  Error!' . PHP_EOL;
                    echo '  ' . ($placeObj->error->message ?? 'Invalid API key.');
                } elseif (403 === intval($placeObj->error->code)) {
                    echo '  Error!' . PHP_EOL;
                    echo '  ' . ($placeObj->error->message ?? 'API is disabled.');
                } elseif (404 === intval($placeObj->error->code)) {
                    echo '  Error!' . PHP_EOL;
                    echo '  ' . ($placeObj->error->message ?? 'Not found this place ID or it is no longer valid.');
                    echo ' (status: ' . $placeObj->error->status . ')';
                } else {
                    echo '  Unknow error.';
                    if (isset($placeObj->error->message)) {
                        echo PHP_EOL;
                        echo '  ' . $placeObj->error->message;
                    }
                }
            }// endif; there is error code.
        }// endif; there is place details or not.

        // save data to DB. ---------------------------------------------------
        $sql = 'INSERT INTO `google_places`  (`place_id`, `place_name`, `last_update`) VALUES (:place_id, :place_name, :last_update)
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
        unset($insertId);
        // end save data to DB. ----------------------------------------------

        echo PHP_EOL . PHP_EOL;
        unset($place, $placeName, $placeObj);
        unset($latLngForURL);
    }// endforeach;
    unset($row);

    echo 'Total ' . count($result) . ' rows, inserted/updated ' . $updated . ' rows.' . PHP_EOL;
    unset($updated);
} else {
    echo 'Total 0 rows found. Nothing to retrieve.' . PHP_EOL;
}
unset($result);


$Db->disconnect();
unset($Db, $dbh);


/**
 * Get Google Place detail.
 * 
 * @link https://developers.google.com/maps/documentation/places/web-service/place-details?hl=en Reference/document.
 * @param string $placeId
 * @return object
 */
function curlGetPlaceDetail(string $placeId)
{
    $ch = curl_init('https://places.googleapis.com/v1/places/' . rawurlencode($placeId) . '?languageCode=th');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Goog-Api-Key: ' . GOOGLE_MAPS_API_KEY,
        'X-Goog-FieldMask: id,displayName',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    unset($ch);
    
    return $response;
}// curlGetPlaceDetail


/**
 * Refresh place ID when it is no longer valid.
 * 
 * @link https://developers.google.com/maps/documentation/places/web-service/place-id#save-id Reference.
 * @param string $placeId
 * @return object
 */
function curlRefreshPlaceId(string $placeId)
{
    $ch = curl_init('https://places.googleapis.com/v1/places/' . rawurlencode($placeId));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Goog-Api-Key: ' . GOOGLE_MAPS_API_KEY,
        'X-Goog-FieldMask: id',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    unset($ch);
    
    return $response;
}// curlRefreshPlaceId