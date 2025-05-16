<?php
/** 
 * Search page.
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

$itemsPerPage = 10;
// setup querystrings. ------------------------------------------------------------------
$act = filter_input(INPUT_GET, 'act');
$act = strip_tags(trim(($act ?? '')));
$keyword = filter_input(INPUT_GET, 'keyword');
$keyword = strip_tags(trim(($keyword ?? '')));
$offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT);
if (!is_numeric($offset) || $offset < 0) {
    $offset = 0;
} elseif (is_numeric($offset)) {
    $offset = intval($offset);
}

$output = [];
$output['search'] = [];
// action for list auto complete. --------------------------------------------------------
if ('autocomplete' === strtolower($act)) {
    // if action for autocomplete.
    $output['search']['autocomplete'] = [];
    // search for Google Place first.
    $sql = 'SELECT `place_name` FROM `google_places` WHERE `place_name` LIKE :keyword ORDER BY `place_name` ASC LIMIT :limit OFFSET 0';
    $Sth = $dbh->prepare($sql);
    unset($sql);
    $Sth->bindValue(':keyword', '%' . $keyword . '%');
    $Sth->bindValue(':limit', $itemsPerPage, \PDO::PARAM_INT);
    $Sth->execute();
    $result = $Sth->fetchAll();
    $Sth->closeCursor();
    unset($Sth);
    if ($result) {
        $words = [];
        foreach ($result as $row) {
            $words[] = extractWord($keyword, $row->place_name);
        }// endforeach;
        unset($row);
    }// endif;
    $output['search']['autocomplete']['words'] = ($words ?? []);
    unset($result, $words);

    $totalWords = count($output['search']['autocomplete']['words']);
    if ($totalWords < $itemsPerPage && !preg_match('/[^\d\.\-, °\'"NSEW]/iu', $keyword)) {
        // if search result is not full and there is keyword look like latitude, longitude.
        $output['search']['autocomplete']['uselatlng'] = true;
        $sql = 'SELECT `topCandidate_placeLocation_latLng` FROM `visit` WHERE `topCandidate_placeLocation_latLng` LIKE :keyword ORDER BY `topCandidate_placeLocation_latLng` ASC LIMIT :limit OFFSET 0';
        $Sth = $dbh->prepare($sql);
        unset($sql);
        $Sth->bindValue(':keyword', '%' . $keyword . '%');
        $Sth->bindValue(':limit', $itemsPerPage, \PDO::PARAM_INT);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        if ($result) {
            $i = 1;
            $words = [];
            foreach ($result as $row) {
                if ($i > ($itemsPerPage - $totalWords)) {
                    break;
                }

                $words[] = extractWord($keyword, $row->topCandidate_placeLocation_latLng);
                ++$i;
            }// endforeach;
            unset($i, $row);
            array_push($output['search']['autocomplete']['words'], ...$words);
            unset($words);
        }// endif;
        unset($result);
    }// endif; search result is not full and there is lat,long keyword.
}// endif;
// end action for list auto complete. ---------------------------------------------------

// action for search. ---------------------------------------------------------------------
if ('' === $act || 'search' === $act) {
    $bindValues = [];
    // count total.
    $sql = 'SELECT `s`.`id`, `s`.`startTime`, `s`.`endTime`
            , `visit`.`visit_id`, `visit`.`topCandidate_placeId`, `visit`.`topCandidate_placeLocation_latLng`
            , `gp`.`place_name`
        FROM `semanticsegments` AS `s`
        INNER JOIN `visit` ON `visit`.`segment_id` = `s`.`id`
        INNER JOIN `google_places` AS `gp` ON `gp`.`place_id` = `visit`.`topCandidate_placeId`
        WHERE `gp`.`place_name` LIKE :keyword OR `visit`.`topCandidate_placeLocation_latLng` LIKE :keyword
        GROUP BY `visit`.`topCandidate_placeLocation_latLng`
        ORDER BY ISNULL(`gp`.`place_name`), `gp`.`place_name` ASC, 
            ISNULL(`visit`.`topCandidate_placeLocation_latLng`), `visit`.`topCandidate_placeLocation_latLng` ASC
    ';
    $Sth = $dbh->prepare($sql);
    $bindValues[':keyword']['value'] = '%' . $keyword . '%';
    $Db->bindValues($Sth, $bindValues);
    $Sth->execute();
    $result = $Sth->fetchAll();
    $Sth->closeCursor();
    $totalResult = (is_countable($result) ? count($result) : 0);
    unset($result);
    $output['search']['result']['itemsPerPage'] = $itemsPerPage;
    $output['search']['result']['total'] = $totalResult;
    $pagesOffset = [];
    for ($i = 0; $i <= $totalResult; $i = ($i + $itemsPerPage)) {
        $pagesOffset[] = $i;
    }
    if (!in_array($offset, $pagesOffset)) {
        $offset = 0;
    }
    $output['search']['result']['pagesOffset'] = $pagesOffset;
    $output['search']['result']['currentOffset'] = $offset;
    unset($pagesOffset, $totalResult);

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
    if (is_array($result) && !empty($result)) {
        // if there is result.
        $output['search']['result']['items'] = $result;
    } else {
        $output['search']['result']['items'] = [];
    }// endif; there is result
    unset($result);
}// endif;
// end action for search. ----------------------------------------------------------------

$Db->disconnect();
unset($Db, $dbh);

echo json_encode($output);


/**
 * Extract word from result.
 * 
 * For example: the result maybe `13 เหรียญ` and keyword is 'เหรีย' then this function will extract to 'เหรียญ' which is full word.
 * 
 * @param string $keyword The search keyword.
 * @param string $result The result string.
 * @return string Return extracted of full word user typing. If nothing matched then return the original result.
 */
function extractWord(string $keyword, string $result): string
{
    $matches = [];
    preg_match('/([\w\.\-_\'"]*' . preg_quote($keyword, '/') . '[\w\.\-_\'"]*)/iu', $result, $matches);
    if (isset($matches[0]) && is_string($matches[0])) {
        return $matches[0];
    }
    return $result;
}// extractWord