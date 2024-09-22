<?php
/**
 * Get a summary's visited place details.
 * 
 * This page was requested from index page.
 * 
 * @package personal maps timeline
 */


require '../config.php';
require '../vendor/autoload.php';


header('Content-Type: application/json; charset=utf-8');

$output = [];

$Db = new \PMTL\Libraries\Db();
$dbh = $Db->connect();

$latitude = filter_input(INPUT_GET, 'lat', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$longitude = filter_input(INPUT_GET, 'lng', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$errorMessage = [];
// validate input data. =============================================
if (empty($latitude) || empty($longitude) || !is_numeric($latitude) || !is_numeric($longitude)) {
    $errorMessage[] = 'Invalid latitude or longitude.';
}

if (!empty($errorMessage)) {
    $output['error']['messages'] = $errorMessage;
    http_response_code(400);
}
// end validate input data. =========================================


if (empty($errorMessage)) {
    // if there is no errors.
    $output['visitedPlace'] = [];
    $googleExportedLatLngFormat = $latitude . '°, ' . $longitude . '°';

    // retrieve all dates history on this place. ====================
    // do not retrieve all dates because data can be too much.
    /*$sql = 'SELECT `id`, `startTime`, `endTime`,
        `visit_id`, `hierarchyLevel`, `topCandidate_placeId`, `topCandidate_placeLocation_latLng`
    FROM `semanticsegments` AS `segments`
    INNER JOIN `visit` ON `segments`.`id` = `visit`.`segment_id`
    WHERE `topCandidate_placeLocation_latLng` = :latlng
    GROUP BY DATE(`startTime`)
    ORDER BY `startTime` ASC';
    $Sth = $dbh->prepare($sql);
    unset($sql);
    $Sth->bindValue(':latlng', $googleExportedLatLngFormat);
    $Sth->execute();
    $result = $Sth->fetchAll();
    $Sth->closeCursor();
    unset($Sth);
    if ($result) {
        $output['visitedPlace']['history'] = [
            'total' => count($result),
            'items' => $result,
        ];
    }
    unset($result);*/
    // retrieve all dates history on this place. ====================

    // retrieve all years history on this place. ====================
    // retrieve history of this place group by latest date of the year.
    $sql = 'WITH `latest_date` AS (
        SELECT `s`.*, ROW_NUMBER() OVER (
            PARTITION BY YEAR(`startTime`) ORDER BY `startTime` DESC
        ) AS ld
        FROM `semanticsegments` AS `s`
        INNER JOIN `visit` ON `visit`.`segment_id` = `s`.`id`
        WHERE `topCandidate_placeLocation_latLng` = :latlng
    )
    SELECT `latest_date`.`id`, `latest_date`.`startTime`, YEAR(`latest_date`.`startTime`) AS `visitYear`, 
        `visit`.`visit_id`, `visit`.`topCandidate_placeId`, `visit`.`topCandidate_placeLocation_latLng`
    FROM `latest_date` 
    INNER JOIN `visit` ON `visit`.`segment_id` = `latest_date`.`id`
    WHERE `ld` = 1 
    GROUP BY `latest_date`.`id`
    ORDER BY `startTime` DESC
    LIMIT 10 OFFSET 0';
    $Sth = $dbh->prepare($sql);
    unset($sql);
    $Sth->bindValue(':latlng', $googleExportedLatLngFormat);
    $Sth->execute();
    $result = $Sth->fetchAll();
    $Sth->closeCursor();
    unset($Sth);
    if ($result) {
        $output['visitedPlace']['history'] = [
            'total' => count($result),
            'items' => $result,
        ];
    }
    unset($result);
    // end retrieve all years history on this place. ================

    unset($googleExportedLatLngFormat);
}// endif; there is no errors.

$Db->disconnect();
unset($Db, $dbh);

echo json_encode($output);