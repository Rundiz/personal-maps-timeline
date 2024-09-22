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
ini_set('memory_limit','2048M');


echo 'Check for data structure in JSON but not exists on DB.' . PHP_EOL;

$JSONFiles = new \PMTL\Libraries\JSONFiles($jsonFolder, $jsonFile);
$tableStructure = new \stdClass();
$tableStructureFlat = new \stdClass();

foreach ($JSONFiles->getFiles() as $file) {
    echo '  Building table structure from file "' . basename($file) . '"' . PHP_EOL;

    $jsonObj = json_decode(file_get_contents($file));
    if (json_last_error() !== JSON_ERROR_NONE) {
        // if there is error in json.
        echo '  ' . json_last_error_msg() . PHP_EOL;
        unset($jsonObj);
        break;
    }// endif; json error.

    $tableStructure = buildSegmentColumnType($jsonObj, $tableStructure);
    foreach ($jsonObj->semanticSegments as $eachSegment) {
        $tableStructure = buildTableStructure($eachSegment, $tableStructure);
    }// end foreach; `semanticSegments` property.
    unset($eachSegment);
    unset($jsonObj);
}// endforeach; list files.
unset($file, $JSONFiles);


$tableStructureClone = unserialize(serialize($tableStructure));// for deep clone. if use `clone` the sub object of cloned one will be use from original object.
$tableStructureFlat = flattenTableStructure($tableStructureClone, $tableStructureFlat);
echo '  Table structure based on JSON: =============' . PHP_EOL;
print_r($tableStructure);
echo '  End table structure based on JSON: =========' . PHP_EOL;
echo '  Flatten: ===================================' . PHP_EOL;
print_r($tableStructureFlat);
echo '  End flatten: ===============================' . PHP_EOL;
echo '  Checking structure with the database: ======' . PHP_EOL;
checkDB($tableStructureFlat);
echo '  End check structurewith the database: ======' . PHP_EOL;

unset($tableStructure, $tableStructureClone, $tableStructureFlat);

$Db->disconnect();
unset($Db, $dbh, $jsonFile, $jsonFolder);

echo 'Finish.' . PHP_EOL;


// ==========================================================================================


/**
 * Build `semanticSegments` table with column type.
 *
 * @param object $jsonObj
 * @param \stdClass $tableStructure
 * @return \stdClass
 */
function buildSegmentColumnType($jsonObj, \stdClass $tableStructure): \stdClass
{
    $tableStructure->semanticSegments = new \stdClass();
    foreach ($jsonObj->semanticSegments as $eachSegment) {
        foreach ($eachSegment as $property => $value) {
            if (isDBValueDataTypes($value)) {
                $tableStructure->semanticSegments->{$property} = '"DB value (' . gettype($value) . ')"';
                unset($eachSegment->{$property});
            }
        }// endforeach; $eachSegment
        unset($eachSegment);
    }// end foreach; `semanticSegments` property.
    unset($eachSegment);

    return $tableStructure;
}// buildSegmentColumnType


/**
 * Build table structure from exported data.
 *
 * @param object $jsonObj
 * @param \stdClass $tableStructure
 * @return \stdClass
 */
function buildTableStructure($jsonObj, \stdClass $tableStructure): \stdClass
{
    if (is_array($jsonObj) || is_object($jsonObj)) {
        foreach ($jsonObj as $property => $item) {
            if (isDBValueDataTypes($item)) {
                $tableStructure->{$property} = '"DB value (' . gettype($item) . ')."';
                unset($jsonObj->{$property});
            } elseif (is_array($item)) {
                if (!property_exists($tableStructure, $property)) {
                    $tableStructure->{$property} = [];
                }

                foreach ($item as $eachItem) {
                    if (!isset($tableStructure->{$property}[0])) {
                        $tableStructure->{$property}[0] = new \stdClass();
                    }

                    $rows = buildTableStructure($eachItem, new \stdClass());
                    foreach ($rows as $rowProperty => $rowValue) {
                        if (isDBValueDataTypes($rowValue) && !property_exists($tableStructure->{$property}[0], $rowProperty)) {
                            $tableStructure->{$property}[0]->$rowProperty = $rowValue;
                        }

                        if (is_object($rowValue)) {
                            $tableStructure->{$property}[0]->{$rowProperty} = new \stdClass();
                            $tableStructure->{$property}[0]->{$rowProperty} = buildTableStructure($rowValue, $tableStructure->{$property}[0]->{$rowProperty});
                        }
                    }// endforeach;
                    unset($rowProperty, $rowValue);
                    unset($rows);
                }// endforeach;
                unset($eachItem);
            } elseif (is_object($item)) {
                if (!property_exists($tableStructure, $property)) {
                    $tableStructure->{$property} = new \stdClass();
                }

                $tableStructure->{$property} = buildTableStructure($item, $tableStructure->{$property});
                unset($jsonObj->{$property});
            }
        }// endforeach;
        //unset($item, $property);
    } else {
        // if $jsonObj not object nor array.
    }// endif;

    return $tableStructure;
}// buildTableStructure


/**
 * Flatten first level of structure.
 *
 * @param \stdClass $tableStructure
 * @param \stdClass $tableStructureFlat
 * @return \stdClass
 */
function flattenLevel1(\stdClass $tableStructure, \stdClass $tableStructureFlat): \stdClass
{
    foreach ($tableStructure as $tableName => $data) {
        if (!property_exists($tableStructureFlat, $tableName)) {
            $tableStructureFlat->{$tableName} = new \stdClass();
        }

        if (is_object($data)) {
            $tableStructureFlat->{$tableName} = flattenLevelData($data, $tableStructureFlat->{$tableName});
        } elseif (is_array($data)) {
            // if data is array (this may be `timelinePath` property).
            foreach ($data as $row) {
                if (is_object($row)) {
                    $tableStructureFlat->{$tableName} = flattenLevelData($row, $tableStructureFlat->{$tableName});
                }// endif; $row
            }// endforeach;
            unset($row);
        }
    }// endforeach;
    unset($data, $tableName);

    return $tableStructureFlat;
}// flattenLevel1


/**
 * Flatten structure where value is matched data type for DB value.
 *
 * @param \stdClass $object
 * @param \stdClass $tableName The table name object to set column name as property and column type as value.
 * @return \stdClass
 */
function flattenLevelData(\stdClass $object, \stdClass $tableName): \stdClass
{
    foreach ($object as $property => $value) {
        if (isDBValueDataTypes($value)) {
            $tableName->{$property} = $value;
            unset($object->{$property});
        }
    }// endforeach;
    unset($property, $value);

    return $tableName;
}// flattenLevelData


/**
 * Create associative array where key is column names based on object and value is their values.
 *
 * @param \stdClass $object
 * @param array $results
 * @param string $prefix
 * @return array
 */
function flattenLevelObject(\stdClass $object, array $results = [], string $prefix = ''): array
{
    foreach ($object as $property => $value) {
        if (is_object($value) && !empty((array) $value)) {
            if (!empty($prefix) && !empty($property)) {
                $prefix .= '_';
            }
            $results = flattenLevelObject($value, $results, $prefix . $property);
        } elseif (isDBValueDataTypes($value)) {
            $prefix = trim($prefix, " \n\r\t\v\0_");
            $results[$prefix . '_' . $property] = $value;
        } elseif (is_array($value)) {
            // if there is an array inside object.
            // this should be new table.
            $results[$prefix . '_' . $property] = $value;
        }
    }// endforeach;
    unset($property, $value);

    return $results;
}// flattenLevelObject


/**
 * Flatten sub array of object where processed via `flattenLevelObject()` function to be new table.
 *
 * @param array $arrayVals
 * @param \stdClass $tableStructureFlat
 * @param string $tableName
 * @return \stdClass
 */
function flattenLevelObjectSubArrayToNewTable(array $arrayVals, \stdClass $tableStructureFlat, string $tableName): \stdClass
{
    if (!property_exists($tableStructureFlat, $tableName)) {
        $tableStructureFlat->{$tableName} = new \stdClass();
    }

    foreach ($arrayVals as $eachArray) {
        if (is_object($eachArray) && !empty((array) $eachArray)) {
            $columns = flattenLevelObject($eachArray);
            foreach ($columns as $name => $value) {
                if (!is_array($value)) {
                    $tableStructureFlat->{$tableName}->{$name} = $value;
                }
            }// endforeach;
        }
    }// endforeach;
    unset($property, $value);

    return $tableStructureFlat;
}// flattenLevelObjectSubArrayToNewTable


/**
 * Flatten table structure.
 *
 * @param \stdClass $tableStructure
 * @param \stdClass $tableStructureFlat
 * @return \stdClass
 */
function flattenTableStructure(\stdClass $tableStructure, \stdClass $tableStructureFlat): \stdClass
{
    $tableStructureFlat = flattenLevel1($tableStructure, $tableStructureFlat);

    foreach ($tableStructure as $tableName => $data) {
        if (is_object($data) && !empty((array) $data)) {
            $columns = flattenLevelObject($data);
            foreach ($columns as $name => $value) {
                if (!is_array($value)) {
                    $tableStructureFlat->{$tableName}->{$name} = $value;
                } elseif (is_array($value)) {
                    $tableStructureFlat = flattenLevelObjectSubArrayToNewTable($value, $tableStructureFlat, $tableName . '_' . $name);
                }
            }// endforeach;
            unset($name, $value);
            unset($columns);
        }// endif;
    }// endforeach;
    unset($data, $tableName);

    return $tableStructureFlat;
}// flattenTableStructure


function checkDB(\stdClass $tableStructureFlat)
{
    global $dbh;
    $errors = 0;

    foreach ($tableStructureFlat as $table => $columns) {
        $table = strtolower($table);
        $sql = 'SHOW TABLES LIKE \'' . $table . '\'';
        $result = $dbh->query($sql);
        unset($sql);
        if (!$result->fetchColumn()) {
            echo '    The table `' . $table . '` is not exists.' . PHP_EOL;
            ++$errors;
        } else {
            foreach ($columns as $column => $value) {
                $sql = 'SHOW COLUMNS FROM `' . $table . '` WHERE `field` = :field';
                $Sth = $dbh->prepare($sql);
                $Sth->bindValue(':field', $column);
                unset($sql);
                $Sth->execute();
                $result = $Sth->fetchColumn();
                $Sth->closeCursor();
                unset($Sth);

                if (!$result) {
                    echo '    The column `' . $table . '`.`' . $column . '` is not exists.' . PHP_EOL;
                    ++$errors;
                }
            }// endfoerach;
            unset($column, $value);
        }
        unset($result);
    }// endforeach;
    unset($columns, $table);

    if ($errors === 0) {
        echo '    [✓] All tables and columns are checked and exists.' . PHP_EOL;
    } else {
        echo '    [!] There is an error found, please read details.' . PHP_EOL;
    }
}// checkDB


/**
 * Check if variable is matched data type for DB value.
 *
 * @param mixed $variable
 * @return boolean
 */
function isDBValueDataTypes($variable): bool
{
    if (is_scalar($variable) || is_null($variable)) {
        return true;
    }
    return false;
}// isDBValueDataTypes

