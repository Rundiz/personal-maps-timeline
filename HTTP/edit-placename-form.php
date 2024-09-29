<?php
/** 
 * Get edit place name form and its data.
 * 
 * @package personal maps timeline
 */


require '../config.php';
require '../vendor/autoload.php';


header('Content-Type: application/json; charset=utf-8');

$output = [];

$Db = new \PMTL\Libraries\Db();
$dbh = $Db->connect();

$placeId = filter_input(INPUT_GET, 'placeId');
$errorMessage = [];
// validate input data. =============================================
if ('' === $placeId) {
    $errorMessage[] = 'Invalid place ID.';
}

if (!empty($errorMessage)) {
    $output['error']['messages'] = $errorMessage;
    http_response_code(400);
}
// end validate input data. =========================================


if (empty($errorMessage)) {
    // if there is no errors.
    $sql = 'SELECT `place_id`, `place_name`, `visit`.`topCandidate_placeLocation_latLng`
    FROM `google_places` 
    LEFT JOIN `visit` ON `visit`.`topCandidate_placeId` = `google_places`.`place_id`
    WHERE `place_id` = :place_id';
    $Sth = $dbh->prepare($sql);
    unset($sql);
    $Sth->bindValue(':place_id', $placeId);
    $Sth->execute();
    $result = $Sth->fetchObject();
    $Sth->closeCursor();
    unset($Sth);
    if ($result) {
        // if found place ID in `google_places` table.
        $place_name = $result->place_name;
    } else {
        // if not found place ID in `google_places` table.
        $place_name = '';

        // query to get place ID from `visit` table and lat, lng can be use.
        $sql = 'SELECT `topCandidate_placeLocation_latLng`, `topCandidate_placeId` FROM `visit` WHERE `topCandidate_placeId` = :place_id';
        $Sth = $dbh->prepare($sql);
        unset($sql);
        $Sth->bindValue(':place_id', $placeId);
        $Sth->execute();
        $resultVisit = $Sth->fetchObject();
        $Sth->closeCursor();
        if ($resultVisit) {
            $result = new \stdClass();
            $result->place_id = $placeId;
            $result->place_name = null;
            $result->topCandidate_placeLocation_latLng = $resultVisit->topCandidate_placeLocation_latLng;
        }// endif; there is result from `visit` table.
        unset($resultVisit);
    }// endif; there is result.
    
    if (isset($result->topCandidate_placeLocation_latLng)) {
        $latLngForURL = preg_replace('/(,\s{1,})/', ',', $result->topCandidate_placeLocation_latLng);
        $latLngForURL = preg_replace('/[^\d\.\-\,]/', '', $latLngForURL);
    }

    $htmlForm = '
<form id="pmtl-edit-place-name-form" method="post">
    <div class="mb-3 row">
        <label for="placeId" class="col-sm-2 col-form-label">Place ID</label>
        <div class="col-sm-10">
            <div class="row">
                <div class="col-12 col-sm-8">
                    <input id="placeId" class="form-control-plaintext" type="text" readonly value="' . htmlspecialchars($placeId, ENT_QUOTES) . '">
                    <input id="place_id" type="hidden" name="place_id" value="' . htmlspecialchars($placeId, ENT_QUOTES) . '">
                </div>
                <div class="col-12 col-sm-auto">
                    <small><a href="https://www.google.com/maps/search/?api=1&query=' . rawurlencode(($latLngForURL ?? 'Maps')) . '&query_place_id=' . rawurlencode($placeId) . '" target="googlemaps">view on Google Maps</a></small>
                </div>
            </div>
        </div>
    </div>
    <div class="mb-3 row">
        <label for="place_name" class="col-sm-2 col-form-label">Place name</label>
        <div class="col-sm-10">
            <input id="place_name" class="form-control" type="text" name="place_name" value="' . $place_name . '" maxlength="190">
        </div>
    </div>
    <div class="mb-3 row">
        <div class="col-sm-10 offset-sm-2">
            <button class="btn btn-primary" type="submit">Save</button>
        </div>
    </div>
</form>';
    $output['result']['htmlForm'] = $htmlForm;
    $output['result']['dbResult'] = $result;
    unset($htmlForm, $latLngForURL, $result);
}// endif; there is no errors.

$Db->disconnect();
unset($Db, $dbh);

echo json_encode($output);
