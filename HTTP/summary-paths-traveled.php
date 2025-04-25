<?php
/** 
 * Get summary data of paths traveled.
 * 
 * This page was requested from index page.
 * 
 * @package personal maps timeline
 */


require '../config.php';
require '../vendor/autoload.php';


ini_set('memory_limit', '2048M');


header('Content-Type: application/json; charset=utf-8');

$output = [];

$Db = new \PMTL\Libraries\Db();
$dbh = $Db->connect();

$year = filter_input(INPUT_GET, 'year');
$errorMessage = [];
// validate input data. =============================================
if (!is_numeric($year) && '' !== $year) {
    $errorMessage[] = 'Invalid year value.';
}
if (is_numeric($year)) {
    $year = intval($year);
}

if (!empty($errorMessage)) {
    $output['error']['messages'] = $errorMessage;
    http_response_code(400);
}
// end validate input data. =========================================


if (empty($errorMessage)) {
    // if there is no errors.
    $Cache = new \PMTL\Libraries\Cache();
    $SimpleCache = $Cache->SimpleCache;
    unset($Cache);
    $cacheKey = __FILE__ . $year;

    if (!$SimpleCache->has($cacheKey)) {
        // list all timelinepath for selected year. ===========================
        $sql = 'WITH `latest_location` AS (
            SELECT `tlp`.*, ROW_NUMBER() OVER (PARTITION BY `point` ORDER BY `tlp_id` DESC) AS LL
            FROM `timelinepath` AS `tlp`';
        if (is_numeric($year)) {
            $sql .= 'INNER JOIN `semanticsegments` AS `s` ON `tlp`.`segment_id` = `s`.`id`
            WHERE (
                (YEAR(`startTime`) = :year)
                OR (YEAR(`startTime`) < :year AND YEAR(`endTime`) >= :year)
                OR (YEAR(`endTime`) = :year)
            )';
        }
        $sql .= '
        )
        SELECT `semanticsegments`.`id` AS `segment_id`,
            `latest_location`.`tlp_id`, `latest_location`.`point` AS `tlp_point`, `latest_location`.`time` AS `tlp_time`
        FROM `latest_location` 
        INNER JOIN `semanticsegments` ON `latest_location`.`segment_id` = `semanticsegments`.`id`
        WHERE `LL` = 1 
        ORDER BY `semanticsegments`.`id` ASC, `tlp_id` ASC';
        $Sth = $dbh->prepare($sql);
        unset($sql);
        if (is_numeric($year)) {
            $Sth->bindValue(':year', $year, \PDO::PARAM_INT);
        }
        $Sth->execute();
        $rawResult = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($Sth);
        if ($rawResult) {
            // if there is result.
            $result = [];
            // re-format data.
            foreach ($rawResult as $row) {
                // setup main table (semanticsegments).
                if (!isset($result[$row->segment_id])) {
                    $result[$row->segment_id] = new \stdClass();
                    $result[$row->segment_id]->segment_id = $row->segment_id;
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
                        unset($columnName, $value);
                        $result[$row->segment_id]->timelinepath[] = $timelinepath;
                        unset($timelinepath);
                    }
                }
            }// endforeach;
            unset($row);
        }
        unset($rawResult);

        $result = array_values($result);
        $output['result'] = [
            'total' => count($result),
            'items' => $result,
        ];
        $SimpleCache->set($cacheKey, $output, 60);
        // end list all timelinepath for selected year. =======================
    } else {
        // if already has cached.
        $output = $SimpleCache->get($cacheKey);
    }// endif; has cached
    unset($cacheKey, $SimpleCache);
}// endif; there is no errors.

$Db->disconnect();
unset($Db, $dbh);

echo json_encode($output);