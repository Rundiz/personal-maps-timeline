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
`google_places`.`place_name`
FROM `visit`
LEFT JOIN `google_places` ON `visit`.`topCandidate_placeId` = `google_places`.`place_id`
WHERE `place_name` IS NULL
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
    $i = 0;
    $updated = 0;
    foreach ($result as $row) {
        echo $row->topCandidate_placeId;
        echo ' (' . $row->countPlaceId . ')';
        echo PHP_EOL;
        $latLngForURL = preg_replace('/(,\s{1,})/', ',', $row->topCandidate_placeLocation_latLng);
        $latLngForURL = preg_replace('/[^\d\.\-\,]/', '', $latLngForURL);
        echo 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($latLngForURL) . '&query_place_id=' . rawurlencode($row->topCandidate_placeId);
        echo PHP_EOL;

        $place = curlGetPlaceDetail($row->topCandidate_placeId);
        $placeObj = json_decode($place);
        if (isset($placeObj->displayName->text)) {
            echo '"' . $placeObj->displayName->text . '"';
            $sql = 'INSERT INTO `google_places`  (`place_id`, `place_name`) VALUES (:place_id, :place_name)
                ON DUPLICATE KEY UPDATE `place_name` = :place_name';
            $Sth = $dbh->prepare($sql);
            unset($sql);
            $Sth->bindValue(':place_id', trim($row->topCandidate_placeId));
            $Sth->bindValue(':place_name', trim($placeObj->displayName->text));
            $Sth->execute();
            $insertId = $dbh->lastInsertId();
            $Sth->closeCursor();
            unset($Sth);
            
            if (false !== $insertId) {
                ++$updated;
                echo '  :: inserted/updated';
            }
            unset($insertId);
        } else {
            if (isset($placeObj->error->code)) {
                if (400 === intval($placeObj->error->code)) {
                    echo 'Error!' . PHP_EOL;
                    echo ($placeObj->error->message ?? 'Invalid API key.');
                    echo PHP_EOL;
                } elseif (403 === intval($placeObj->error->code)) {
                    echo 'Error!' . PHP_EOL;
                    echo ($placeObj->error->message ?? 'API is disabled.');
                    echo PHP_EOL;
                }
                break;
            }// endif; there is error code.

            if (isset($placeObj->error->message)) {
                echo $placeObj->error->message . PHP_EOL;
            }
        }// endif; there is place details or not.
        echo PHP_EOL . PHP_EOL;
        ++$i;
        unset($place, $placeObj);
        unset($latLngForURL);
    }
    unset($row);

    echo 'Total ' . $i . ' rows, inserted/updated ' . $updated . ' rows.' . PHP_EOL;
    unset($i, $updated);
}
unset($result);


$Db->disconnect();
unset($Db, $dbh);


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
}