<?php
/**
 * Get summary data by year.
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

$year = filter_input(INPUT_GET, 'year', FILTER_SANITIZE_NUMBER_INT);
$errorMessage = [];
// validate input data. =============================================
if (empty($year) || !is_numeric($year)) {
    $errorMessage[] = 'Invalid year value.';
}

if (!empty($errorMessage)) {
    $output['error']['messages'] = $errorMessage;
    http_response_code(400);
}
// end validate input data. =========================================


if (empty($errorMessage)) {
    // if there is no errors.
    // list all visits for selected year. ===========================
    $sql = 'WITH `latest_location` AS (
        SELECT `v`.*, ROW_NUMBER() OVER (PARTITION BY `topCandidate_placeLocation_latLng` ORDER BY `visit_id` DESC) AS LL
        FROM `visit` AS `v`
        INNER JOIN `semanticsegments` AS `s` ON `v`.`segment_id` = `s`.`id`
        WHERE (
            (YEAR(`startTime`) = :year)
            OR (YEAR(`startTime`) < :year AND YEAR(`endTime`) >= :year)
            OR (YEAR(`endTime`) = :year)
        )
    )
    SELECT `semanticsegments`.`id`, `semanticsegments`.`startTime`, 
        `latest_location`.`visit_id`, `latest_location`.`topCandidate_placeId`, `latest_location`.`topCandidate_placeLocation_latLng`
    FROM `latest_location` 
    INNER JOIN `semanticsegments` ON `latest_location`.`segment_id` = `semanticsegments`.`id`
    WHERE `LL` = 1 
    ORDER BY `visit_id` ASC';
    $Sth = $dbh->prepare($sql);
    unset($sql);
    $Sth->bindValue(':year', $year, \PDO::PARAM_INT);
    $Sth->execute();
    $result = $Sth->fetchAll();
    $Sth->closeCursor();
    unset($Sth);
    if ($result) {
        // if there is result.
        $output['visitedPlacesYear'] = [
            'total' => count($result),
            'items' => $result,
        ];
    }
    unset($result);
    // end list all visits for selected year. =======================
}// endif; there is no errors.

$Db->disconnect();
unset($Db, $dbh);

echo json_encode($output);