<?php
/** 
 * Get visited history base on selected year and/or month or nothing.
 * 
 * @package personal maps timeline
 */


require '../config.php';
require '../vendor/autoload.php';


header('Content-Type: application/json; charset=utf-8');

$output = [];

$Db = new \PMTL\Libraries\Db();
$dbh = $Db->connect();

$itemsPerPage = 10;
$placeId = filter_input(INPUT_GET, 'placeId');
$year = filter_input(INPUT_GET, 'year', FILTER_SANITIZE_NUMBER_INT);
$month = filter_input(INPUT_GET, 'month', FILTER_SANITIZE_NUMBER_INT);
$offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT);
$errorMessage = [];
// validate input data. =============================================
if (empty($placeId)) {
    $errorMessage[] = 'The `placeId` value is required.';
}
if ((empty($year) && !empty($month))) {
    $errorMessage[] = 'Year must be selected.';
}
if (!empty($year) && !preg_match('/\d{4}/', $year)) {
    $errorMessage[] = 'Year must be number.';
}
if (!empty($month) && (!is_numeric($month) || intval($month) > 12 || intval($month) < 1)) {
    $errorMessage[] = 'Invalid month value.';
}
if (empty($errorMessage) && !empty($month)) {
    $startYearMonth = $year . '-' . sprintf('%02d', $month) . '-01';
    $endYearMonth = date('Y-m-t', strtotime($startYearMonth));
}

if (!empty($errorMessage)) {
    $output['error']['messages'] = $errorMessage;
    http_response_code(400);
}
// end validate input data. =========================================
if ('' === $placeId) {
    $placeId = null;
}
if ('' === $year) {
    $year = null;
}
if ('' === $month) {
    $month = null;
}
if (!is_numeric($offset) || $offset < 0) {
    $offset = 0;
} elseif (is_numeric($offset)) {
    $offset = intval($offset);
}



if (empty($errorMessage)) {
    // if there is no errors.
    $output['visitedHistory'] = [];

    if (empty($year)) {
        // if not selected year.
        // list all years visited.
        $sql = 'SELECT DISTINCT YEAR(`startTime`) AS `yearVisited` FROM `visit`
        INNER JOIN `semanticsegments` AS `s` ON `segment_id` = `s`.`id`
        WHERE 1';
        if (isset($placeId)) {
            $sql .= ' AND `topCandidate_placeId` = :placeId';
        }
        $sql .= ' UNION';
        $sql .= ' SELECT DISTINCT YEAR(`endTime`) AS `yearVisited` FROM `visit`
        INNER JOIN `semanticsegments` AS `s` ON `segment_id` = `s`.`id`
        WHERE 1';
        if (isset($placeId)) {
            $sql .= ' AND `topCandidate_placeId` = :placeId';
        }
        $Sth = $dbh->prepare($sql);
        unset($sql);
        if (isset($placeId)) {
            $Sth->bindValue(':placeId', $placeId);
        }
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($Sth);
        if (isset($result)) {
            $yearsVisited = [];
            foreach ($result as $row) {
                if (!in_array($row->yearVisited, $yearsVisited)) {
                    $yearsVisited[] = $row->yearVisited;
                }
            }// endforeach;
            unset($row);
            $output['visitedHistory']['yearsVisited'] = $yearsVisited;
            unset($yearsVisited);
        }
        unset($result);
    }// endif; not selected year.

    // list dates visited limit on nn numbers. -------------------------------
    $bindValues = [];
    // count total.
    $sql = 'SELECT *
        FROM (
            SELECT DISTINCT `visit_id`, `segment_id`, `topCandidate_placeId`
                , `startTime`, `endTime`, DATE(`startTime`) AS `dateVisited`
            FROM `visit`
            INNER JOIN `semanticsegments` AS `s` ON `segment_id` = `s`.`id`
            UNION
            SELECT DISTINCT `visit_id`, `segment_id`, `topCandidate_placeId`
                , `startTime`, `endTime`, DATE(`endTime`) AS `dateVisited`
            FROM `visit`
            INNER JOIN `semanticsegments` AS `s` ON `segment_id` = `s`.`id`
        ) AS `date_visited_union`
        WHERE 1';
    if (isset($placeId)) {
        $sql .= ' AND `topCandidate_placeId` = :placeId';
        $bindValues[':placeId']['value'] = $placeId;
    }
    if (isset($year) && !isset($month)) {
        $sql .= ' AND (
            (YEAR(`dateVisited`) = :year)
            OR (YEAR(`dateVisited`) < :year AND YEAR(`dateVisited`) >= :year)
            OR (YEAR(`dateVisited`) = :year)
        )';
        $bindValues[':year']['value'] = $year;
        $bindValues[':year']['type'] = \PDO::PARAM_INT;
    } elseif (isset($month)) {
        $sql .= ' AND (
            (YEAR(`dateVisited`) = :year AND MONTH(`dateVisited`) = :month)
            OR (YEAR(`dateVisited`) = :year AND MONTH(`dateVisited`) = :month)
            OR (
                DATE(`dateVisited`) <= :startYearMonth AND (
                    DATE(`dateVisited`) >= :endYearMonth OR DATE(`dateVisited`) >= :startYearMonth
                )
            )
        )';
        $bindValues[':year']['value'] = $year;
        $bindValues[':year']['type'] = \PDO::PARAM_INT;
        $bindValues[':month']['value'] = sprintf('%02d', $month);
        $bindValues[':startYearMonth']['value'] = $startYearMonth;
        $bindValues[':endYearMonth']['value'] = $endYearMonth;
    }
    $sql .= ' GROUP BY `dateVisited` 
        ORDER BY `date_visited_union`.`dateVisited` DESC';
    $Sth = $dbh->prepare($sql);
    $Db->bindValues($Sth, $bindValues);
    $Sth->execute();
    $result = $Sth->fetchAll();
    $Sth->closeCursor();
    unset($Sth);
    $totalDatesVisited = (is_countable($result) ? count($result) : 0);
    unset($result);
    $output['visitedHistory']['datesVisited']['itemsPerPage'] = $itemsPerPage;
    $output['visitedHistory']['datesVisited']['total'] = $totalDatesVisited;
    $pagesOffset = [];
    for ($i = 0; $i <= $totalDatesVisited; $i = ($i + $itemsPerPage)) {
        $pagesOffset[] = $i;
    }
    if (!in_array($offset, $pagesOffset)) {
        $offset = 0;
    }
    $output['visitedHistory']['datesVisited']['pagesOffset'] = $pagesOffset;
    $output['visitedHistory']['datesVisited']['currentOffset'] = $offset;
    unset($pagesOffset, $totalDatesVisited);

    // list all with paginated.
    $sql .= ' LIMIT :limit OFFSET :offset';
    $bindValues[':limit']['value'] = $itemsPerPage;
    $bindValues[':limit']['type'] = \PDO::PARAM_INT;
    $bindValues[':offset']['value'] = $offset;
    $bindValues[':offset']['type'] = \PDO::PARAM_INT;
    $Sth = $dbh->prepare($sql);
    unset($sql);
    $Db->bindValues($Sth, $bindValues);
    $Sth->execute();
    $result = $Sth->fetchAll();
    $Sth->closeCursor();
    unset($Sth);
    if (isset($result)) {
        $output['visitedHistory']['datesVisited']['items'] = $result;
    }
    unset($result);
    // end list dates visited. --------------------------------------------------

    unset($endYearMonth, $startYearMonth);
}// endif; there is no errors.

$Db->disconnect();
unset($Db, $dbh);

echo json_encode($output);