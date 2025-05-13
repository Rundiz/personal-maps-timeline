<?php
/**
 * Get a summary of visited place such as first year and last year.
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
    $minMaxYear = new \stdClass();

    // retrieve first and last year visited base on latitude and longitude only. --------------
    $sql = 'SELECT YEAR(MIN(`startTime`)) AS `startYear`, YEAR(MAX(`endTime`)) AS `endYear`
        , `v`.`visit_id`, `v`.`topCandidate_placeId`, `v`.`topCandidate_placeLocation_latLng`
    FROM `visit` AS `v`
    INNER JOIN `semanticsegments` AS `s` ON `v`.`segment_id` = `s`.`id`
    WHERE `topCandidate_placeLocation_latLng` = :latlng
    ORDER BY `startYear` ASC, `endYear` ASC';
    $Sth = $dbh->prepare($sql);
    unset($sql);
    $Sth->bindValue(':latlng', $googleExportedLatLngFormat);
    $Sth->execute();
    $row = $Sth->fetchObject();
    $Sth->closeCursor();
    unset($Sth);
    if ($row) {
        $output['visitedPlace']['summaryDataBaseOnLatLng'] = $row;
        $minMaxYear = $row;
        $placeId = $row->topCandidate_placeId;
    }
    unset($row);

    // retrieve first and last year visited base on Google Place ID. ------------------------------
    if (isset($placeId)) {
        $sql = 'SELECT * 
        FROM (
            SELECT DISTINCT YEAR(`startTime`) AS `yearVisited` 
                , YEAR(`startTime`) AS `startYear`, YEAR(`endTime`) AS `endYear`
                , `visit_id`, `topCandidate_placeId`, `topCandidate_placeLocation_latLng`
            FROM `visit`
            INNER JOIN `semanticsegments` AS `s` ON `segment_id` = `s`.`id`
            WHERE `topCandidate_placeId` = :placeId
            UNION
            SELECT DISTINCT YEAR(`endTime`) AS `yearVisited` 
                , YEAR(`startTime`) AS `startYear`, YEAR(`endTime`) AS `endYear`
                , `visit_id`, `topCandidate_placeId`, `topCandidate_placeLocation_latLng`
            FROM `visit`
            INNER JOIN `semanticsegments` AS `s` ON `segment_id` = `s`.`id`
        ) AS `yearVisitedUnion`
        WHERE `topCandidate_placeId` = :placeId
        GROUP BY `yearVisited`
        ORDER BY `yearVisitedUnion`.`yearVisited` ASC';
        $Sth = $dbh->prepare($sql);
        unset($sql);
        $Sth->bindValue(':placeId', $placeId);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($Sth);
        if (isset($result)) {
            $output['visitedPlace']['summaryDataBaseOnPlaceId'] = $result;
            foreach ($result as $row) {
                if ($row->yearVisited < $minMaxYear->startYear) {
                    $minMaxYear->startYear = $row->yearVisited;
                }
                if ($row->yearVisited > $minMaxYear->endYear) {
                    $minMaxYear->endYear = $row->yearVisited;
                }
            }// endforeach;
            unset($row);
        }// endif; there is fetched result.
        unset($placeId);
        unset($result);
    }// endif; there is place ID value.

    $output['visitedPlace']['minMaxYears'] = $minMaxYear;
    unset($minMaxYear);

    unset($googleExportedLatLngFormat);
}// endif; there is no errors.

$Db->disconnect();
unset($Db, $dbh);

echo json_encode($output);