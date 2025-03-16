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


$JSONFiles = new \PMTL\Libraries\JSONFiles($jsonFolder, $jsonFile);
$total = 0;
$totalInserted = 0;
$totalInsertVisit = 0;
$totalUpdateVisit = 0;
$totalInsertActivity = 0;
$totalUpdateActivity = 0;
$totalInsertTLPath = 0;
$totalUpdateTLPath = 0;
$totalInsertTLM = 0;
$totalUpdateTLM = 0;
$totalInsertTLMTD = 0;
$totalUpdateTLMTD = 0;

foreach ($JSONFiles->getFiles() as $file) {
    $totalPerFile = 0;
    echo 'Import from file "' . basename($file) . '"' . PHP_EOL;

    $jsonObj = json_decode(file_get_contents($file));
    if (json_last_error() !== JSON_ERROR_NONE) {
        // if there is error in json.
        echo '  ' . json_last_error_msg() . PHP_EOL;
        unset($jsonObj);
        break;
    }// endif; json error.

    if (isset($jsonObj->semanticSegments) && is_iterable($jsonObj->semanticSegments)) {
        foreach ($jsonObj->semanticSegments as $eachSegment) {
            ++$total;
            ++$totalPerFile;

            // insert location segment and its related data.
            insertSegment($eachSegment);
        }// endforeach; semanticSegments
        unset($eachSegment);
    }
    unset($jsonObj);

    echo '  ';
    printf(
        ngettext('There is total %d segment for this file.', 'There are total %d segments for this file.', $totalPerFile),
        $totalPerFile
    );
    echo PHP_EOL;
}// endforeach; list files.
unset($file, $JSONFiles);

$Db->disconnect();
unset($Db, $dbh, $jsonFile, $jsonFolder);


// displaying result. ===============================================================
printf(
    ngettext('There is total %d segment from all files.', 'There are total %d segments from all files.', $total),
    $total
);
echo PHP_EOL;
printf(
    ngettext('Total inserted/updated %d segment.', 'Total inserted/updated %d segments.', $totalInserted),
    $totalInserted
);
echo PHP_EOL;

echo '  ';
printf(
    'Total inserted %d visit data.',
    $totalInsertVisit
);
echo ' ';
printf(
    'Total updated %d visit data.',
    $totalUpdateVisit
);
echo PHP_EOL;

echo '  ';
printf(
    'Total inserted %d activity data.',
    $totalInsertActivity
);
echo ' ';
printf(
    'Total updated %d activity data.',
    $totalUpdateActivity
);
echo PHP_EOL;

echo '  ';
printf(
    'Total inserted %d timelinePath data.',
    $totalInsertTLPath
);
echo ' ';
printf(
    'Total updated %d timelinePath data.',
    $totalUpdateTLPath
);
echo PHP_EOL;

echo '  ';
printf(
    'Total inserted %d timeline memory data.',
    $totalInsertTLM
);
echo ' ';
printf(
    'Total updated %d timeline memory data.',
    $totalUpdateTLM
);
echo PHP_EOL;

echo '  ';
printf(
    'Total inserted %d timeline memory trip destinations data.',
    $totalInsertTLMTD
);
echo ' ';
printf(
    'Total updated %d timeline memory trip destinations data.',
    $totalUpdateTLMTD
);
echo PHP_EOL;
unset($total, $totalInserted, $totalPerFile);
unset($totalInsertVisit, $totalUpdateVisit);
unset($totalInsertActivity, $totalUpdateActivity);
unset($totalInsertTLPath, $totalUpdateTLPath);
unset($totalInsertTLM, $totalUpdateTLM);
unset($totalInsertTLMTD, $totalUpdateTLMTD);


// ==========================================================================================


/**
 * Insert location segment and its related data.
 *
 * @param object $segment
 * @return void
 */
function insertSegment($segment)
{
    if (!is_object($segment)) {
        throw new \Exception('The `$segment` argument must be an object.');
    }

    global $dbh;

    $segment_id = isSegmentExists($segment);
    $segmentExists = false;
    if (false === $segment_id) {
        // if segment_id is not exists (never insert before).
        $startDt = new \DateTime($segment->startTime);
        $endDt = new \DateTime($segment->endTime);

        $sql = 'INSERT INTO `semanticsegments` SET 
            `startTime` = :startTime, 
            `endTime` = :endTime, 
            `startTimeTimezoneUtcOffsetMinutes` = :startTimeTimezoneUtcOffsetMinutes, 
            `endTimeTimezoneUtcOffsetMinutes` = :endTimeTimezoneUtcOffsetMinutes';
        $Sth = $dbh->prepare($sql);
        unset($sql);
        $Sth->bindValue(':startTime', $startDt->format('Y-m-d H:i:s'));
        $Sth->bindValue(':endTime', $endDt->format('Y-m-d H:i:s'));
        unset($endDt, $startDt);
        $Sth->bindValue(':startTimeTimezoneUtcOffsetMinutes', ($segment->startTimeTimezoneUtcOffsetMinutes ?? null), \PDO::PARAM_INT);
        $Sth->bindValue(':endTimeTimezoneUtcOffsetMinutes', ($segment->endTimeTimezoneUtcOffsetMinutes ?? null), \PDO::PARAM_INT);
        $Sth->execute();
        $segment_id = intval($dbh->lastInsertId());
        $Sth->closeCursor();
        unset($Sth);

        global $totalInserted;
        ++$totalInserted;
    } else {
        // if segment_id exists.
        $segmentExists = true;
    }// endif; segment_id exists or not.

    if (isset($segment->activity) && is_object($segment->activity)) {
        insertUpdateActivity($segment_id, $segment->activity);
    }// endif; `activity` property.

    if (isset($segment->visit) && is_object($segment->visit)) {
        insertUpdateVisit($segment_id, $segment->visit);
    }// endif; `visit` property.

    if (isset($segment->timelinePath) && is_iterable($segment->timelinePath)) {
        insertUpdateTimelinePath($segment_id, $segmentExists, $segment->timelinePath);
    }// endif; `timelinePath` property.

    if (isset($segment->timelineMemory) && is_object($segment->timelineMemory)) {
        insertUpdateTimelineMemory($segment_id, $segment->timelineMemory);
    }// endif; `timelineMemory` property.

    unset($segment_id, $segmentExists);
}// insertSegment


/**
 * Insert or update activity data.
 *
 * @param integer $segment_id
 * @param object $activity
 * @return void
 */
function insertUpdateActivity(int $segment_id, $activity)
{
    if (!is_object($activity)) {
        throw new \Exception('The argument `$activity` must be an object.');
    }

    global $dbh;
    global $totalInsertActivity, $totalUpdateActivity;

    // check data exists.
    $sql = 'SELECT `activity_id`, `segment_id`, `start_latLng`, `end_latLng` FROM `activity`
        WHERE `segment_id` = :segment_id
        AND `start_latLng` = :start_latLng
        AND `end_latLng` = :end_latLng';
    $Sth = $dbh->prepare($sql);
    unset($sql);
    $Sth->bindValue(':segment_id', $segment_id, \PDO::PARAM_INT);
    $Sth->bindValue(':start_latLng', ($activity->start->latLng ?? null));
    $Sth->bindValue(':end_latLng', ($activity->end->latLng ?? null));
    $Sth->execute();
    $row = $Sth->fetchObject();
    $Sth->closeCursor();
    unset($Sth);
    if (!$row) {
        $activity_id = false;
    } else {
        $activity_id = $row->activity_id;
    }
    unset($row);
    // end check data exists.

    if (isset($activity->parking->startTime)) {
        $startTimeDt = new \DateTime($activity->parking->startTime);
    }

    if (false === $activity_id) {
        // if data is not exists.
        $sql = 'INSERT INTO `activity` SET `segment_id` = :segment_id, 
            `start_latLng` = :start_latLng, 
            `end_latLng` = :end_latLng, 
            `distanceMeters` = :distanceMeters, 
            `probability` = :probability, 
            `topCandidate_type` = :topCandidate_type, 
            `topCandidate_probability` = :topCandidate_probability, 
            `parking_location_latLng` = :parking_location_latLng,
            `parking_startTime` = :parking_startTime';
        ++$totalInsertActivity;
    } else {
        // if data exists.
        $sql = 'UPDATE `activity` SET `probability` = :probability, 
            `distanceMeters` = :distanceMeters, 
            `probability` = :probability, 
            `topCandidate_type` = :topCandidate_type, 
            `topCandidate_probability` = :topCandidate_probability, 
            `parking_location_latLng` = :parking_location_latLng,
            `parking_startTime` = :parking_startTime
            WHERE `activity_id` = :activity_id';
        ++$totalUpdateActivity;
    }

    $Sth = $dbh->prepare($sql);
    unset($sql);
    if (false === $activity_id) {
        $Sth->bindValue(':segment_id', $segment_id, \PDO::PARAM_INT);
        $Sth->bindValue(':start_latLng', ($activity->start->latLng ?? null));
        $Sth->bindValue(':end_latLng', ($activity->end->latLng ?? null));
    } else {
        $Sth->bindValue(':activity_id', $activity_id);
    }
    $Sth->bindValue(':distanceMeters', ($activity->distanceMeters ?? null));
    $Sth->bindValue(':probability', ($activity->probability ?? null));
    $Sth->bindValue(':topCandidate_type', ($activity->topCandidate->type ?? null));
    $Sth->bindValue(':topCandidate_probability', ($activity->topCandidate->probability ?? null));
    $Sth->bindValue(':parking_location_latLng', ($activity->parking->location->latLng ?? null));
    $Sth->bindValue(':parking_startTime', (isset($startTimeDt) ? $startTimeDt->format('Y-m-d H:i:s') : null));
    $Sth->execute();
    $Sth->closeCursor();
    unset($Sth);
    unset($activity_id, $startDt);
}// insertUpdateActivity


/**
 * Insert or update timeline memory and its children data.
 *
 * @param integer $segment_id
 * @param object $timelineMemory
 * @return void
 */
function insertUpdateTimelineMemory(int $segment_id, $timelineMemory)
{
    if (!is_object($timelineMemory)) {
        throw new \Exception('The argument `$timelineMemory` must be an object.');
    }

    global $dbh;
    global $totalInsertTLM, $totalUpdateTLM;

    // check data exists.
    $sql = 'SELECT `tmem_id`, `segment_id`, `trip_distanceFromOriginKms` FROM `timelinememory`
        WHERE `segment_id` = :segment_id
        AND `trip_distanceFromOriginKms` = :trip_distanceFromOriginKms';
    $Sth = $dbh->prepare($sql);
    unset($sql);
    $Sth->bindValue(':segment_id', $segment_id, \PDO::PARAM_INT);
    $Sth->bindValue(':trip_distanceFromOriginKms', ($timelineMemory->trip->distanceFromOriginKms ?? null));
    $Sth->execute();
    $row = $Sth->fetchObject();
    $Sth->closeCursor();
    unset($Sth);
    if (!$row) {
        $tmem_id = false;
    } else {
        $tmem_id = $row->tmem_id;
    }
    unset($row);
    // end check data exists.

    if (false === $tmem_id) {
        // if data is not exists.
        $sql = 'INSERT INTO `timelinememory` SET `segment_id` = :segment_id, 
            `trip_distanceFromOriginKms` = :trip_distanceFromOriginKms';
        $Sth = $dbh->prepare($sql);
        unset($sql);
        $Sth->bindValue(':segment_id', $segment_id, \PDO::PARAM_INT);
        $Sth->bindValue(':trip_distanceFromOriginKms', ($timelineMemory->trip->distanceFromOriginKms ?? null));
        $Sth->execute();
        $tmem_id = $dbh->lastInsertId();
        $Sth->closeCursor();
        unset($Sth);

        ++$totalInsertTLM;
    }

    if (isset($timelineMemory->trip->destinations) && is_array($timelineMemory->trip->destinations)) {
        insertUpdateTimelineMemoryTD($tmem_id, $timelineMemory->trip->destinations);
    }
    unset($tmem_id);
}// insertUpdateTimelineMemory


/**
 * Insert or update timeline memory trip destinations data.
 *
 * @param integer $tmem_id
 * @param array $destinations
 * @return void
 */
function insertUpdateTimelineMemoryTD(int $tmem_id, array $destinations)
{
    if (!is_array($destinations)) {
        throw new \Exception('The argument `$destinations` must be an array.');
    }

    global $dbh;
    global $totalInsertTLMTD, $totalUpdateTLMTD;

    foreach ($destinations as $destination) {
        // check data exists.
        $sql = 'SELECT `tmem_id`, `identifier_placeId` FROM `timelinememory_trip_destinations`
            WHERE `tmem_id` = :tmem_id
            AND `identifier_placeId` = :identifier_placeId';
        $Sth = $dbh->prepare($sql);
        unset($sql);
        $Sth->bindValue(':tmem_id', $tmem_id, \PDO::PARAM_INT);
        $Sth->bindValue(':identifier_placeId', ($destination->identifier->placeId ?? null));
        $Sth->execute();
        $row = $Sth->fetchObject();
        $Sth->closeCursor();
        unset($Sth);
        if (!$row) {
            $tmem_trip_dest_id = false;
        } else {
            $tmem_trip_dest_id = $row->tmem_id;
        }
        unset($row);
        // end check data exists.

        if (false === $tmem_trip_dest_id) {
            // if data is not exists.
            $sql = 'INSERT INTO `timelinememory_trip_destinations` SET `tmem_id` = :tmem_id, 
                `identifier_placeId` = :identifier_placeId';
            $Sth = $dbh->prepare($sql);
            unset($sql);
            $Sth->bindValue(':tmem_id', $tmem_id, \PDO::PARAM_INT);
            $Sth->bindValue(':identifier_placeId', $destination->identifier->placeId);
            $Sth->execute();
            $Sth->closeCursor();
            unset($Sth);

            ++$totalInsertTLMTD;
        }
        unset($tmem_trip_dest_id);
    }// endforeach;
    unset($destination);
}// insertUpdateTimelineMemoryTD


/**
 * Insert or update timeline path data.
 *
 * @param integer $segment_id
 * @param boolean $segmentExists
 * @param array $timelinePath
 * @return void
 */
function insertUpdateTimelinePath(int $segment_id, bool $segmentExists, array $timelinePath)
{
    if (!is_array($timelinePath)) {
        throw new \Exception('The argument `$timelinePath` must be an array.');
    }

    global $dbh;
    global $totalInsertTLPath, $totalUpdateTLPath;

    if (true === $segmentExists) {
        // if a segment exists.
        // this will be insert timeline path to the existing segment (start and end time).
        // delete current timeline path data before insert otherwise the data can be duplicated.
        $sql = 'DELETE FROM `timelinepath` WHERE `segment_id` = :segment_id';
        $Sth = $dbh->prepare($sql);
        unset($sql);
        $Sth->bindValue(':segment_id', $segment_id, \PDO::PARAM_INT);
        $Sth->execute();
        $totalUpdateTLPath = ($totalUpdateTLPath + $Sth->rowCount());
        $Sth->closeCursor();
        unset($Sth);
    }// endif; segment exists.

    foreach ($timelinePath as $eachData) {
        $timelinePathTime = new \DateTime($eachData->time);

        // insert into `timelinepath` table.
        $sql = 'INSERT INTO `timelinepath` SET `segment_id` = :segment_id, 
            `point` = :point, 
            `time` = :time';
        $Sth = $dbh->prepare($sql);
        unset($sql);
        $Sth->bindValue(':segment_id', $segment_id, \PDO::PARAM_INT);
        $Sth->bindValue(':point', ($eachData->point ?? null));
        $Sth->bindValue(':time', ($timelinePathTime->format('Y-m-d H:i:s') ?? null));
        $Sth->execute();
        $Sth->closeCursor();
        unset($Sth, $timelinePathTime);

        if (false === $segmentExists) {
            ++$totalInsertTLPath;
        }
    }// endforeach;
}// insertUpdateTimelinePath


/**
 * Insert or update visit data.
 *
 * @param integer $segment_id
 * @param object $visit
 * @return void
 */
function insertUpdateVisit(int $segment_id, $visit)
{
    if (!is_object($visit)) {
        throw new \Exception('The argument `$visit` must be an object.');
    }

    global $dbh;
    global $totalInsertVisit, $totalUpdateVisit;

    // check data exists.
    $sql = 'SELECT `visit_id`, `segment_id`, `hierarchyLevel` FROM `visit`
        WHERE `segment_id` = :segment_id
        AND `hierarchyLevel` = :hierarchyLevel';
    $Sth = $dbh->prepare($sql);
    unset($sql);
    $Sth->bindValue(':segment_id', $segment_id, \PDO::PARAM_INT);
    $Sth->bindValue(':hierarchyLevel', ($visit->hierarchyLevel ?? null), \PDO::PARAM_INT);
    $Sth->execute();
    $row = $Sth->fetchObject();
    $Sth->closeCursor();
    unset($Sth);
    if (!$row) {
        $visit_id = false;
    } else {
        $visit_id = $row->visit_id;
    }
    unset($row);
    // end check data exists.

    if (false === $visit_id) {
        // if data is not exists.
        $sql = 'INSERT INTO `visit` SET `segment_id` = :segment_id, 
            `hierarchyLevel` = :hierarchyLevel, 
            `probability` = :probability, 
            `topCandidate_placeId` = :topCandidate_placeId, 
            `topCandidate_semanticType` = :topCandidate_semanticType, 
            `topCandidate_probability` = :topCandidate_probability, 
            `topCandidate_placeLocation_latLng` = :topCandidate_placeLocation_latLng, 
            `isTimelessVisit` = :isTimelessVisit';
        ++$totalInsertVisit;
    } else {
        // if data exists.
        $sql = 'UPDATE `visit` SET `probability` = :probability, 
            `topCandidate_placeId` = :topCandidate_placeId, 
            `topCandidate_semanticType` = :topCandidate_semanticType, 
            `topCandidate_probability` = :topCandidate_probability, 
            `topCandidate_placeLocation_latLng` = :topCandidate_placeLocation_latLng, 
            `isTimelessVisit` = :isTimelessVisit
            WHERE `visit_id` = :visit_id';
        ++$totalUpdateVisit;
    }

    $Sth = $dbh->prepare($sql);
    unset($sql);
    if (false === $visit_id) {
        $Sth->bindValue(':segment_id', $segment_id, \PDO::PARAM_INT);
        $Sth->bindValue(':hierarchyLevel', ($visit->hierarchyLevel ?? null), \PDO::PARAM_INT);
    } else {
        $Sth->bindValue(':visit_id', $visit_id);
    }
    $Sth->bindValue(':probability', ($visit->probability ?? null));
    $Sth->bindValue(':topCandidate_placeId', ($visit->topCandidate->placeId ?? null));
    $Sth->bindValue(':topCandidate_semanticType', ($visit->topCandidate->semanticType ?? null));
    $Sth->bindValue(':topCandidate_probability', ($visit->topCandidate->probability ?? null));
    $Sth->bindValue(':topCandidate_placeLocation_latLng', ($visit->topCandidate->placeLocation->latLng ?? null));
    $Sth->bindValue(':isTimelessVisit', ($visit->isTimelessVisit ?? null), \PDO::PARAM_BOOL);
    $Sth->execute();
    $Sth->closeCursor();
    unset($Sth);
    unset($visit_id);
}// insertUpdateVisit


/**
 * Check is segment exists
 *
 * @param object $segment
 * @return number|false Return segment `id` column if exists, return `false` if not exists.
 */
function isSegmentExists($segment)
{
    if (!is_object($segment)) {
        throw new \Exception('The `$segment` argument must be an object.');
    }
    $startDt = new \DateTime($segment->startTime);
    $endDt = new \DateTime($segment->endTime);

    global $dbh;

    $sql = 'SELECT `id`, `startTime`, `endTime`, `startTimeTimezoneUtcOffsetMinutes`, `endTimeTimezoneUtcOffsetMinutes`
        FROM `semanticsegments` 
        WHERE `startTime` = :startTime
        AND `endTime` = :endTime';
    /*$sql .= '
        AND `startTimeTimezoneUtcOffsetMinutes` = :startTimeTimezoneUtcOffsetMinutes
        AND `endTimeTimezoneUtcOffsetMinutes` = :endTimeTimezoneUtcOffsetMinutes';*/
    $Sth = $dbh->prepare($sql);
    $Sth->bindValue(':startTime', $startDt->format('Y-m-d H:i:s'));
    $Sth->bindValue(':endTime', $endDt->format('Y-m-d H:i:s'));
    /*$Sth->bindValue(':startTimeTimezoneUtcOffsetMinutes', ($segment->startTimeTimezoneUtcOffsetMinutes ?? null));
    $Sth->bindValue(':endTimeTimezoneUtcOffsetMinutes', ($segment->endTimeTimezoneUtcOffsetMinutes ?? null));*/
    $Sth->execute();
    $row = $Sth->fetchObject();
    $Sth->closeCursor();
    unset($sql, $Sth);
    if (!$row) {
        return false;
    }
    return intval($row->id);
}// isSegmentExists