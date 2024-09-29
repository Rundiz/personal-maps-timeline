<?php
/**
 * Get timeline data by date.
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

$date = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_NUMBER_INT);
$errorMessage = [];
// validate input data. =============================================
if (empty($date)) {
    $errorMessage[] = 'Please enter a date.';
} else {
    if (!preg_match('/(\d{4})\-(\d{2})\-(\d{2})/i', $date)) {
        $errorMessage[] = 'Invalid date format.';
    }
}

if (!empty($errorMessage)) {
    $output['error']['messages'] = $errorMessage;
    http_response_code(400);
}
// end validate input data. =========================================


/**
 * Set isTimelessVisit value.
 *
 * @param object $row
 * @return bool|null
 */
function setIstimelessVisitValue($row): ?bool
{
    if (in_array($row->visit_isTimelessVisit, ['1', 1, true], true)) {
        $value = true;
    } elseif (in_array($row->visit_isTimelessVisit, ['0', 0, false], true)) {
        $value = false;
    } else {
        $value = null;
    }

    return $value;
}// setIstimelessVisitValue


if (empty($errorMessage)) {
    // if there is no errors.
    $sql = 'SELECT `segments`.`id`, 
        `segments`.`startTime`, 
        `segments`.`endTime`, 
        `segments`.`startTimeTimezoneUtcOffsetMinutes`, 
        `segments`.`endTimeTimezoneUtcOffsetMinutes`,
        `activity`.`activity_id`,
        `activity`.`segment_id` AS `activity_segment_id`, 
        `activity`.`start_latLng` AS `activity_start_latLng`,
        `activity`.`end_latLng` AS `activity_end_latLng`,
        `activity`.`distanceMeters` AS `activity_distanceMeters`,
        `activity`.`probability` AS `activity_probability`,
        `activity`.`topCandidate_type` AS `activity_topCandidate_type`,
        `activity`.`topCandidate_probability` AS `activity_topCandidate_probability`,
        `activity`.`parking_location_latLng` AS `activity_parking_location_latLng`,
        `activity`.`parking_startTime` AS `activity_parking_startTime`,
        `visit`.`visit_id`,
        `visit`.`segment_id` AS `visit_segment_id`, 
        `visit`.`hierarchyLevel` AS `visit_hierarchyLevel`,
        `visit`.`probability` AS `visit_probability`, 
        `visit`.`topCandidate_placeId` AS `visit_topCandidate_placeId`,
        `visit`.`topCandidate_semanticType` AS `visit_topCandidate_semanticType`,
        `visit`.`topCandidate_probability` AS `visit_topCandidate_probability`,
        `visit`.`topCandidate_placeLocation_latLng` AS `visit_topCandidate_placeLocation_latLng`,
        `visit`.`isTimelessVisit` AS `visit_isTimelessVisit`,
        `google_places`.`place_name` AS `visit_place_name`,
        `tlp`.`tlp_id`,
        `tlp`.`segment_id` AS `tlp_segment_id`,
        `tlp`.`point` AS `tlp_point`,
        `tlp`.`time` AS `tlp_time`
    FROM `semanticsegments` AS `segments` 
    LEFT JOIN `activity` ON `segments`.`id` = `activity`.`segment_id`
    LEFT JOIN `visit` ON `segments`.`id` = `visit`.`segment_id`
    LEFT JOIN `timelinepath` AS `tlp` ON `segments`.`id` = `tlp`.`segment_id`
    LEFT JOIN `google_places` ON `visit`.`topCandidate_placeId` = `google_places`.`place_id`
    WHERE (
        (DATE(`startTime`) = :date)
        OR (DATE(`startTime`) < :date AND DATE(`endTime`) >= :date)
        OR (DATE(`endTime`) = :date)
    )
    ORDER BY `id` ASC';
    $Sth = $dbh->prepare($sql);
    unset($sql);
    $Sth->bindValue(':date', $date);
    $Sth->execute();
    $rawResult = $Sth->fetchAll();
    $Sth->closeCursor();
    unset($Sth);

    if ($rawResult) {
        // if there is raw result.
        $result = [];
        // re-format data.
        foreach ($rawResult as $index => $row) {
            // setup main table (semanticsegments).
            if (!isset($result[$row->id])) {
                $result[$row->id] = new \stdClass();
                $result[$row->id]->id = $row->id;
                $result[$row->id]->startTime = $row->startTime;
                $result[$row->id]->endTime = $row->endTime;
                $result[$row->id]->startTimeTimezoneUtcOffsetMinutes = $row->startTimeTimezoneUtcOffsetMinutes;
                $result[$row->id]->endTimeTimezoneUtcOffsetMinutes = $row->endTimeTimezoneUtcOffsetMinutes;
            }

            // set property for table `activity`.
            if (property_exists($row, 'activity_id')) {
                if (isset($row->activity_id)) {
                    $result[$row->id]->activity = new \stdClass();
                    $result[$row->id]->activity->activity_id = $row->activity_id;
                    unset($row->activity_id);
                    // iterate over column names.
                    foreach ($row as $columnName => $value) {
                        if (stripos($columnName, 'activity_') === 0) {
                            $formattedColName = preg_replace('/^' . preg_quote('activity_', '/') . '/i', '', $columnName);
                            $result[$row->id]->activity->{$formattedColName} = $value;
                            unset($row->{$columnName});
                            unset($formattedColName);
                        }
                    }// endforeach; iterate over column names.
                } else {
                    $result[$row->id]->activity = null;
                }// endif; `$row->activity_id` was set.
            } else {
                $result[$row->id]->activity = null;
            }// endif; `activity` property.

            // set property for table `visit`.
            if (property_exists($row, 'visit_id')) {
                if (isset($row->visit_id)) {
                    if (!isset($result[$row->id]->visit->visit_id)) {
                        // if there is no `visit->visit_id` property.
                        $result[$row->id]->visit = new \stdClass();
                        $result[$row->id]->visit->visit_id = $row->visit_id;
                        unset($row->visit_id);
                        // iterate over column names.
                        foreach ($row as $columnName => $value) {
                            if (stripos($columnName, 'visit_') === 0) {
                                if ($columnName === 'isTimelessVisit') {
                                    $value = setIstimelessVisitValue($row);
                                }
                                $formattedColName = preg_replace('/^' . preg_quote('visit_', '/') . '/i', '', $columnName);
                                $result[$row->id]->visit->{$formattedColName} = $value;
                                unset($row->{$columnName});
                                unset($formattedColName);
                            }
                        }// endforeach; iterate over column names.
                    } else {
                        // if there is `visit->visit_id` property already.
                        // set this visit place to `subVisits` property.
                        if (!property_exists($result[$row->id]->visit, 'subVisits')) {
                            $result[$row->id]->visit->subVisits = [];
                        }
                        $subVisit = new \stdClass();
                        $subVisit->visit_id = $row->visit_id;
                        unset($row->visit_id);
                        // iterate over column names.
                        foreach ($row as $columnName => $value) {
                            if (stripos($columnName, 'visit_') === 0) {
                                if ($columnName === 'isTimelessVisit') {
                                    $value = setIstimelessVisitValue($row);
                                }
                                $formattedColName = preg_replace('/^' . preg_quote('visit_', '/') . '/i', '', $columnName);
                                $subVisit->{$formattedColName} = $value;
                                unset($row->{$columnName});
                                unset($formattedColName);
                            }
                        }// endforeach; iterate over column names.
                        $result[$row->id]->visit->subVisits[] = $subVisit;
                        unset($subVisit);
                    }// endif;
                } else {
                    $result[$row->id]->visit = null;
                }// endif; `$row->visit_id` was set.
            } else {
                $result[$row->id]->visit = null;
            }// endif; `visit` property.

            // set property for `timelinepath`. ---------------------
            if (!property_exists($result[$row->id], 'timelinepath')) {
                $result[$row->id]->timelinepath = [];
            }
            
            if (property_exists($row, 'tlp_id')) {
                if (isset($row->tlp_id)) {
                    $timelinepath = new \stdClass();
                    $timelinepath->tlp_id = $row->tlp_id;
                    unset($row->tlp_id);
                    // iterate over column names.
                    foreach ($row as $columnName => $value) {
                        if (stripos($columnName, 'tlp_') === 0) {
                            $formattedColName = preg_replace('/^' . preg_quote('tlp_', '/') . '/i', '', $columnName);
                            $timelinepath->{$formattedColName} = $value;
                            unset($row->{$columnName});
                            unset($formattedColName);
                        }
                    }// endforeach; iterate over column names.
                    $result[$row->id]->timelinepath[] = $timelinepath;
                    unset($timelinepath);
                }
            }
            // end set property for `timelinepath`. -----------------

            unset($row->segment_id);// it is already in `id` property.
        }// endforeach;
        unset($index, $row);
        unset($rawResult);

        $result = array_values($result);
        $output['result'] = [
            'total' => count($result),
            'items' => $result,
        ];
    }// endif; there is raw result.
    unset($rawResult);
}// endif; there is no errors.

$Db->disconnect();
unset($Db, $dbh);

echo json_encode($output);