<?php
/** 
 * Edit place name (save data).
 * 
 * @package personal maps timeline
 */


require '../config.php';
require '../vendor/autoload.php';


header('Content-Type: application/json; charset=utf-8');

$output = [];

$Db = new \PMTL\Libraries\Db();
$dbh = $Db->connect();

$place_id = filter_input(INPUT_POST, 'place_id');
$place_name = filter_input(INPUT_POST, 'place_name');
$errorMessage = [];
// validate input data. =============================================
if ('' === $place_id) {
    $errorMessage[] = 'Invalid place ID.';
}
if (!is_string($place_name) || '' === trim($place_name)) {
    $errorMessage[] = 'Please enter place name.';
} else {
    $place_name = htmlspecialchars(trim($place_name), ENT_QUOTES);
}

if (!empty($errorMessage)) {
    $output['error']['messages'] = $errorMessage;
    http_response_code(400);
}
// end validate input data. =========================================


if (empty($errorMessage)) {
    // if there is no errors.
    $sql = 'INSERT INTO `google_places`  (`place_id`, `place_name`) VALUES (:place_id, :place_name)
        ON DUPLICATE KEY UPDATE `place_name` = :place_name';
    $Sth = $dbh->prepare($sql);
    unset($sql);
    $Sth->bindValue(':place_id', $place_id);
    $Sth->bindValue(':place_name', $place_name);
    $Sth->execute();
    $insertId = $dbh->lastInsertId();
    $Sth->closeCursor();
    unset($Sth);

    $output['result'] = [
        'insertId' => $insertId,
        'success' => (false !== $insertId ? true : false),
    ];
    unset($insertId);
}// endif; there is no errors.

$Db->disconnect();
unset($Db, $dbh);

echo json_encode($output);