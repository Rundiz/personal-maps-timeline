<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace PMTL\CLI\Commands;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Import Google timeline exported JSON file to DB.
 */
class ImportJsonToDB extends Command
{


    use Traits\OutputStyles;


    /**
     * @var \PDO
     */
    private $dbh;


    private $totalPerFile = 0;
    private $total = 0;
    private $totalInserted = 0;
    private $totalInsertVisit = 0;
    private $totalUpdateVisit = 0;
    private $totalInsertActivity = 0;
    private $totalUpdateActivity = 0;
    private $totalInsertTLPath = 0;
    private $totalUpdateTLPath = 0;
    private $totalInsertTLM = 0;
    private $totalUpdateTLM = 0;
    private $totalInsertTLMTD = 0;
    private $totalUpdateTLMTD = 0;


    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setName('import-json-to-db');
        $this->setDescription('Import Google timeline JSON to DB.');
        $this->setAliases(['importjsontodb']);
    }// configure


    /**
     * Display the results.
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function displayResult(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('--------------------');

        $output->writeln(
            sprintf(
                ngettext('There is total %d segment from all files.', 'There are total %d segments from all files.', $this->total),
                $this->total
            )
        );
        $output->writeln(
            sprintf(
                ngettext('Total inserted/updated %d segment.', 'Total inserted/updated %d segments.', $this->totalInserted),
                $this->totalInserted
            )
        );

        $output->writeln(
            sprintf(
                'Total inserted %d visit data.',
                $this->totalInsertVisit
            )
        );
        $output->writeln(
            sprintf(
                'Total updated %d visit data.',
                $this->totalUpdateVisit
            )
        );

        $output->writeln(
            sprintf(
                'Total inserted %d activity data.',
                $this->totalInsertActivity
            )
        );
        $output->writeln(
            sprintf(
                'Total updated %d activity data.',
                $this->totalUpdateActivity
            )
        );

        $output->writeln(
            sprintf(
                'Total inserted %d timelinePath data.',
                $this->totalInsertTLPath
            )
        );
        $output->writeln(
            sprintf(
                'Total updated %d timelinePath data.',
                $this->totalUpdateTLPath
            )
        );

        $output->writeln(
            sprintf(
                'Total inserted %d timeline memory data.',
                $this->totalInsertTLM
            )
        );
        $output->writeln(
            sprintf(
                'Total updated %d timeline memory data.',
                $this->totalUpdateTLM
            )
        );

        $output->writeln(
            sprintf(
                'Total inserted %d timeline memory trip destinations data.',
                $this->totalInsertTLMTD
            )
        );
        $output->writeln(
            sprintf(
                'Total updated %d timeline memory trip destinations data.',
                $this->totalUpdateTLMTD
            )
        );
    }// displayResult


    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->symfonyOutput = $output;
        $this->defineOutputStyle();

        global $jsonFile, $jsonFolder;

        $Db = new \PMTL\Libraries\Db();
        $this->dbh = $Db->connect();

        $JSONFiles = new \PMTL\Libraries\JSONFiles($jsonFolder, $jsonFile);

        foreach ($JSONFiles->getFiles() as $file) {
            $this->totalPerFile = 0;
            $output->writeln('Import from file "' . basename($file) . '"');
            $output->writeln('This progress may be very slow, please wait.');

            $jsonObj = json_decode(file_get_contents($file));
            if (json_last_error() !== JSON_ERROR_NONE) {
                // if there is error in json.
                $output->writeln('  <error>' . json_last_error_msg() . '</error>');
                unset($jsonObj);
                break;
            }// endif; json error.

            if (isset($jsonObj->semanticSegments) && is_iterable($jsonObj->semanticSegments)) {
                foreach ($jsonObj->semanticSegments as $eachSegment) {
                    ++$this->total;
                    ++$this->totalPerFile;

                    // insert location segment and its related data.
                    $this->insertSegment($eachSegment);
                }// endforeach; semanticSegments
                unset($eachSegment);
            }
            unset($jsonObj);

            $outputecho = '  ';
            $outputecho .= sprintf(
                ngettext('There is total %d segment for this file.', 'There are total %d segments for this file.', $this->totalPerFile),
                $this->totalPerFile
            );
            $output->writeln($outputecho);
            unset($outputecho);
        }// endforeach; list files.
        unset($file, $JSONFiles);

        $Db->disconnect();
        unset($Db, $jsonFile, $jsonFolder);

        // displaying result. ========================================
        $this->displayResult($input, $output);
        // ===================================================

        $this->resetTotalProperties();

        return Command::SUCCESS;
    }// execute


    /**
     * Insert location segment and its related data.
     *
     * @param object $segment
     * @throws \InvalidArgumentException Throw exception if invalid argument.
     */
    private function insertSegment($segment)
    {
        if (!is_object($segment)) {
            throw new \InvalidArgumentException('The `$segment` argument must be an object.');
        }

        $segment_id = $this->isSegmentExists($segment);
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
            $Sth = $this->dbh->prepare($sql);
            unset($sql);
            $Sth->bindValue(':startTime', $startDt->format('Y-m-d H:i:s'));
            $Sth->bindValue(':endTime', $endDt->format('Y-m-d H:i:s'));
            unset($endDt, $startDt);
            $Sth->bindValue(':startTimeTimezoneUtcOffsetMinutes', ($segment->startTimeTimezoneUtcOffsetMinutes ?? null), \PDO::PARAM_INT);
            $Sth->bindValue(':endTimeTimezoneUtcOffsetMinutes', ($segment->endTimeTimezoneUtcOffsetMinutes ?? null), \PDO::PARAM_INT);
            $Sth->execute();
            $segment_id = intval($this->dbh->lastInsertId());
            $Sth->closeCursor();
            unset($Sth);

            ++$this->totalInserted;
        } else {
            // if segment_id exists.
            $segmentExists = true;
        }// endif; segment_id exists or not.

        if (isset($segment->activity) && is_object($segment->activity)) {
            $this->insertUpdateActivity($segment_id, $segment->activity);
        }// endif; `activity` property.

        if (isset($segment->visit) && is_object($segment->visit)) {
            $this->insertUpdateVisit($segment_id, $segment->visit);
        }// endif; `visit` property.

        if (isset($segment->timelinePath) && is_iterable($segment->timelinePath)) {
            $this->insertUpdateTimelinePath($segment_id, $segmentExists, $segment->timelinePath);
        }// endif; `timelinePath` property.

        if (isset($segment->timelineMemory) && is_object($segment->timelineMemory)) {
            $this->insertUpdateTimelineMemory($segment_id, $segment->timelineMemory);
        }// endif; `timelineMemory` property.

        unset($segment_id, $segmentExists);
    }// insertSegment


    /**
     * Insert or update activity data.
     *
     * @param int $segment_id
     * @param object $activity
     * @throws \InvalidArgumentException Throw exception if invalid argument.
     */
    private function insertUpdateActivity(int $segment_id, $activity)
    {
        if (!is_object($activity)) {
            throw new \InvalidArgumentException('The argument `$activity` must be an object.');
        }

        // check data exists.
        $sql = 'SELECT `activity_id`, `segment_id`, `start_latLng`, `end_latLng` FROM `activity`
            WHERE `segment_id` = :segment_id
            AND `start_latLng` = :start_latLng
            AND `end_latLng` = :end_latLng';
        $Sth = $this->dbh->prepare($sql);
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
            ++$this->totalInsertActivity;
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
            ++$this->totalUpdateActivity;
        }

        $Sth = $this->dbh->prepare($sql);
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
     * @throws \InvalidArgumentException Throw exception if invalid argument.
     */
    private function insertUpdateTimelineMemory(int $segment_id, $timelineMemory)
    {
        if (!is_object($timelineMemory)) {
            throw new \InvalidArgumentException('The argument `$timelineMemory` must be an object.');
        }

        // check data exists.
        $sql = 'SELECT `tmem_id`, `segment_id`, `trip_distanceFromOriginKms` FROM `timelinememory`
            WHERE `segment_id` = :segment_id';
        $Sth = $this->dbh->prepare($sql);
        unset($sql);
        $Sth->bindValue(':segment_id', $segment_id, \PDO::PARAM_INT);
        $Sth->execute();
        $row = $Sth->fetchObject();
        $Sth->closeCursor();
        unset($Sth);
        if (!$row) {
            $tmem_id = false;
        } else {
            $tmem_id = intval($row->tmem_id);
        }
        unset($row);
        // end check data exists.

        if (false === $tmem_id) {
            // if data is not exists.
            $sql = 'INSERT INTO `timelinememory` SET `segment_id` = :segment_id, 
                `trip_distanceFromOriginKms` = :trip_distanceFromOriginKms';
            $Sth = $this->dbh->prepare($sql);
            unset($sql);
            $Sth->bindValue(':segment_id', $segment_id, \PDO::PARAM_INT);
            $Sth->bindValue(':trip_distanceFromOriginKms', ($timelineMemory->trip->distanceFromOriginKms ?? null));
            $Sth->execute();
            $tmem_id = $this->dbh->lastInsertId();
            if (false !== $tmem_id) {
                $tmem_id = intval($tmem_id);
            }
            $Sth->closeCursor();
            unset($Sth);

            ++$this->totalInsertTLM;
        } else {
            // if data exists.
            $sql = 'UPDATE `timelinememory` SET `trip_distanceFromOriginKms` = :trip_distanceFromOriginKms
                WHERE `tmem_id` = :tmem_id';
            $Sth = $this->dbh->prepare($sql);
            unset($sql);
            $Sth->bindValue(':tmem_id', $tmem_id, \PDO::PARAM_INT);
            $Sth->bindValue(':trip_distanceFromOriginKms', ($timelineMemory->trip->distanceFromOriginKms ?? null));
            $Sth->execute();
            $Sth->closeCursor();
            unset($Sth);

            ++$this->totalUpdateTLM;
        }

        if (isset($timelineMemory->trip->destinations) && is_array($timelineMemory->trip->destinations)) {
            $this->insertUpdateTimelineMemoryTD($tmem_id, $timelineMemory->trip->destinations);
        }
        unset($tmem_id);
    }// insertUpdateTimelineMemory


    /**
     * Insert or update timeline memory trip destinations data.
     *
     * @param integer $tmem_id
     * @param array $destinations
     * @throws \InvalidArgumentException Throw exception if invalid argument.
     */
    private function insertUpdateTimelineMemoryTD(int $tmem_id, array $destinations)
    {
        if (!is_array($destinations)) {
            throw new \InvalidArgumentException('The argument `$destinations` must be an array.');
        }

        foreach ($destinations as $destination) {
            // check data exists.
            $sql = 'SELECT `tmem_id`, `identifier_placeId` FROM `timelinememory_trip_destinations`
                WHERE `tmem_id` = :tmem_id
                AND `identifier_placeId` = :identifier_placeId';
            $Sth = $this->dbh->prepare($sql);
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
                $Sth = $this->dbh->prepare($sql);
                unset($sql);
                $Sth->bindValue(':tmem_id', $tmem_id, \PDO::PARAM_INT);
                $Sth->bindValue(':identifier_placeId', $destination->identifier->placeId);
                $Sth->execute();
                $Sth->closeCursor();
                unset($Sth);

                ++$this->totalInsertTLMTD;
            }
            unset($tmem_trip_dest_id);
        }// endforeach;
        unset($destination);
    }// insertUpdateTimelineMemoryTD


    /**
     * Insert or update timeline path data.
     *
     * @param int $segment_id
     * @param bool $segmentExists
     * @param array $timelinePath
     * @throws \InvalidArgumentException Throw exception if invalid argument.
     */
    private function insertUpdateTimelinePath(int $segment_id, bool $segmentExists, array $timelinePath)
    {
        if (!is_array($timelinePath)) {
            throw new \InvalidArgumentException('The argument `$timelinePath` must be an array.');
        }

        if (true === $segmentExists) {
            // if a segment exists.
            // this will be insert timeline path to the existing segment (start and end time).
            // delete current timeline path data before insert otherwise the data can be duplicated.
            $sql = 'DELETE FROM `timelinepath` WHERE `segment_id` = :segment_id';
            $Sth = $this->dbh->prepare($sql);
            unset($sql);
            $Sth->bindValue(':segment_id', $segment_id, \PDO::PARAM_INT);
            $Sth->execute();
            $this->totalUpdateTLPath = ($this->totalUpdateTLPath + $Sth->rowCount());
            $Sth->closeCursor();
            unset($Sth);
        }// endif; segment exists.

        foreach ($timelinePath as $eachData) {
            $timelinePathTime = new \DateTime($eachData->time);

            // insert into `timelinepath` table.
            $sql = 'INSERT INTO `timelinepath` SET `segment_id` = :segment_id, 
                `point` = :point, 
                `time` = :time';
            $Sth = $this->dbh->prepare($sql);
            unset($sql);
            $Sth->bindValue(':segment_id', $segment_id, \PDO::PARAM_INT);
            $Sth->bindValue(':point', ($eachData->point ?? null));
            $Sth->bindValue(':time', ($timelinePathTime->format('Y-m-d H:i:s') ?? null));
            $Sth->execute();
            $Sth->closeCursor();
            unset($Sth, $timelinePathTime);

            if (false === $segmentExists) {
                ++$this->totalInsertTLPath;
            }
        }// endforeach;
    }// insertUpdateTimelinePath


    /**
     * Insert or update visit data.
     *
     * @param int $segment_id
     * @param object $visit
     * @throws \InvalidArgumentException Throw exception if invalid argument.
     */
    private function insertUpdateVisit(int $segment_id, $visit)
    {
        if (!is_object($visit)) {
            throw new \InvalidArgumentException('The argument `$visit` must be an object.');
        }

        // check data exists.
        $sql = 'SELECT `visit_id`, `segment_id`, `hierarchyLevel` FROM `visit`
            WHERE `segment_id` = :segment_id
            AND `hierarchyLevel` = :hierarchyLevel';
        $Sth = $this->dbh->prepare($sql);
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
            ++$this->totalInsertVisit;
        } else {
            // if data exists.
            $sql = 'UPDATE `visit` SET `probability` = :probability, 
                `topCandidate_placeId` = :topCandidate_placeId, 
                `topCandidate_semanticType` = :topCandidate_semanticType, 
                `topCandidate_probability` = :topCandidate_probability, 
                `topCandidate_placeLocation_latLng` = :topCandidate_placeLocation_latLng, 
                `isTimelessVisit` = :isTimelessVisit
                WHERE `visit_id` = :visit_id';
            ++$this->totalUpdateVisit;
        }

        $Sth = $this->dbh->prepare($sql);
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
     * @return int|false Return segment `id` column if exists, return `false` if not exists.
     * @throws \InvalidArgumentException Throw exception if invalid argument.
     */
    private function isSegmentExists($segment)
    {
        if (!is_object($segment)) {
            throw new \InvalidArgumentException('The `$segment` argument must be an object.');
        }
        $startDt = new \DateTime($segment->startTime);
        $endDt = new \DateTime($segment->endTime);

        $sql = 'SELECT `id`, `startTime`, `endTime`, `startTimeTimezoneUtcOffsetMinutes`, `endTimeTimezoneUtcOffsetMinutes`
            FROM `semanticsegments` 
            WHERE `startTime` = :startTime
            AND `endTime` = :endTime';
        $Sth = $this->dbh->prepare($sql);
        $Sth->bindValue(':startTime', $startDt->format('Y-m-d H:i:s'));
        $Sth->bindValue(':endTime', $endDt->format('Y-m-d H:i:s'));
        $Sth->execute();
        $row = $Sth->fetchObject();
        $Sth->closeCursor();
        unset($sql, $Sth);
        if (!$row) {
            return false;
        }
        return intval($row->id);
    }// isSegmentExists


    /**
     * Reset properties that is prefixed with `total`.
     */
    private function resetTotalProperties()
    {
        $this->totalPerFile = 0;
        $this->total = 0;
        $this->totalInsertActivity = 0;
        $this->totalInsertTLM = 0;
        $this->totalInsertTLMTD = 0;
        $this->totalInsertTLPath = 0;
        $this->totalInsertVisit = 0;
        $this->totalInserted = 0;
        $this->totalUpdateActivity = 0;
        $this->totalUpdateTLM = 0;
        $this->totalUpdateTLMTD = 0;
        $this->totalUpdateTLPath = 0;
        $this->totalUpdateVisit = 0;
    }// resetTotalProperties


}
