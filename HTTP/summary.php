<?php
/**
 * Get summary data.
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

// summary oldest and newest date/time. =======================================
$sql = 'SELECT MIN(LEAST(`startTime`, `endTime`)) AS `minDateTime`
    ,MAX(GREATEST(`startTime`, `endTime`)) AS `maxDateTime` 
    FROM `semanticsegments`';
$Sth = $dbh->prepare($sql);
unset($sql);
$Sth->execute();
$row = $Sth->fetchObject();
$Sth->closeCursor();
unset($Sth);
if ($row) {
    $oldDt = new \DateTime($row->minDateTime);
    $latestDt = new \DateTime($row->maxDateTime);
    $output['recordDates'] = [
        'sinceYear' => $oldDt->format('Y'),
        'sinceDate' => $oldDt->format('Y-m-d'),
        'latestYear' => $latestDt->format('Y'),
        'latestDate' => $latestDt->format('Y-m-d'),
    ];
    unset($latestDt, $oldDt);
}// endif;
unset($row);
// end summary oldest and newest date/time. ===================================

// summary total visits. ======================================================
$sql = 'SELECT COUNT(DISTINCT `topCandidate_placeLocation_latLng`) AS `totalVisitU`
    , COUNT(`topCandidate_placeLocation_latLng`) AS `totalVisit` 
    FROM `visit`';
$Sth = $dbh->prepare($sql);
unset($sql);
$Sth->execute();
$row = $Sth->fetchObject();
$Sth->closeCursor();
unset($Sth);
if ($row) {
    $output['totalVisit'] = [
        'unique' => $row->totalVisitU,
        'all' => $row->totalVisit,
    ];
}
unset($row);
// end summary total visits. ==================================================

// list all visits uniquely. ==================================================
// @link https://stackoverflow.com/a/1313293/128761 get latest record from unique location.
$sql = 'WITH `latest_location` AS (
    SELECT `v`.*, ROW_NUMBER() OVER (PARTITION BY `topCandidate_placeLocation_latLng` ORDER BY `visit_id` DESC) AS LL
    FROM `visit` AS `v`
)
SELECT `semanticsegments`.`id`, `semanticsegments`.`startTime`, 
    `latest_location`.`visit_id`, `latest_location`.`topCandidate_placeId`, `latest_location`.`topCandidate_placeLocation_latLng`,
    `google_places`.`place_name`
FROM `latest_location` 
INNER JOIN `semanticsegments` ON `latest_location`.`segment_id` = `semanticsegments`.`id`
LEFT JOIN `google_places` ON `latest_location`.`topCandidate_placeId` = `google_places`.`place_id`
WHERE `LL` = 1 
ORDER BY `visit_id` ASC';
$Sth = $dbh->prepare($sql);
unset($sql);
$Sth->execute();
$result = $Sth->fetchAll();
$Sth->closeCursor();
unset($Sth);
if ($result) {
    // if there is result.
    $output['visitedPlaces'] = [
        'total' => count($result),
        'items' => $result,
    ];
}
unset($result);
// end list all visits uniquely. ==============================================

$Db->disconnect();
unset($Db, $dbh);

echo json_encode($output);