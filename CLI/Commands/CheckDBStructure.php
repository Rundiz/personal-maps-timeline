<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace PMTL\CLI\Commands;


use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Check DB structure class.
 */
#[AsCommand(
        name: 'check-db-structure',
        description: 'Check for data structure in JSON but not exists on DB.',
        hidden: false,
        aliases: ['checkdbstructure'],
)]
class CheckDBStructure extends Command
{


    use Traits\OutputStyles;


    /**
     * @var \PDO
     */
    private $dbh;


    /**
     * Build `semanticSegments` table with column type.
     *
     * @param object $jsonObj
     * @param \stdClass $tableStructure
     * @return \stdClass
     */
    private function buildSegmentColumnType($jsonObj, \stdClass $tableStructure): \stdClass
    {
        $tableStructure->semanticSegments = new \stdClass();
        foreach ($jsonObj->semanticSegments as $eachSegment) {
            foreach ($eachSegment as $property => $value) {
                if ($this->isDBValueDataTypes($value)) {
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
    private function buildTableStructure($jsonObj, \stdClass $tableStructure): \stdClass
    {
        if (is_array($jsonObj) || is_object($jsonObj)) {
            foreach ($jsonObj as $property => $item) {
                if ($this->isDBValueDataTypes($item)) {
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

                        $rows = $this->buildTableStructure($eachItem, new \stdClass());
                        foreach ($rows as $rowProperty => $rowValue) {
                            if ($this->isDBValueDataTypes($rowValue) && !property_exists($tableStructure->{$property}[0], $rowProperty)) {
                                $tableStructure->{$property}[0]->$rowProperty = $rowValue;
                            }

                            if (is_object($rowValue)) {
                                $tableStructure->{$property}[0]->{$rowProperty} = new \stdClass();
                                $tableStructure->{$property}[0]->{$rowProperty} = $this->buildTableStructure($rowValue, $tableStructure->{$property}[0]->{$rowProperty});
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

                    $tableStructure->{$property} = $this->buildTableStructure($item, $tableStructure->{$property});
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
     * Check that table structure exists on DB.
     * 
     * @param \stdClass $tableStructureFlat
     */
    private function checkDB(\stdClass $tableStructureFlat)
    {
        $errors = 0;

        foreach ($tableStructureFlat as $table => $columns) {
            $table = strtolower($table);
            $sql = 'SHOW TABLES LIKE \'' . $table . '\'';
            $result = $this->dbh->query($sql);
            unset($sql);
            if (!$result->fetchColumn()) {
                $this->symfonyOutput->writeln('    The table `' . $table . '` is not exists.');
                ++$errors;
            } else {
                foreach ($columns as $column => $value) {
                    $sql = 'SHOW COLUMNS FROM `' . $table . '` WHERE `field` = :field';
                    $Sth = $this->dbh->prepare($sql);
                    $Sth->bindValue(':field', $column);
                    unset($sql);
                    $Sth->execute();
                    $result = $Sth->fetchColumn();
                    $Sth->closeCursor();
                    unset($Sth);

                    if (!$result) {
                        $this->symfonyOutput->writeln('    The column `' . $table . '`.`' . $column . '` is not exists.');
                        ++$errors;
                    }
                }// endfoerach;
                unset($column, $value);
            }
            unset($result);
        }// endforeach;
        unset($columns, $table);

        if ($errors === 0) {
            $this->symfonyOutput->writeln('    <success>[✓] All tables and columns are checked and exists.</success>');
        } else {
            $this->symfonyOutput->writeln('    <warning>[!] There is an error found, please read details.</warning>');
        }
    }// checkDB


    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
    }// configure


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

        $output->writeln('Check for data structure in JSON but not exists on DB.');

        $JSONFiles = new \PMTL\Libraries\JSONFiles($jsonFolder, $jsonFile);
        $tableStructure = new \stdClass();
        $tableStructureFlat = new \stdClass();

        foreach ($JSONFiles->getFiles() as $file) {
            $output->writeln('Building table structure from file "' . basename($file) . '"');

            $jsonObj = json_decode(file_get_contents($file));
            if (json_last_error() !== JSON_ERROR_NONE) {
                // if there is error in json.
                $output->writeln('  <error>' . json_last_error_msg() . '</error>');
                unset($jsonObj);
                break;
            }// endif; json error.

            $tableStructure = $this->buildSegmentColumnType($jsonObj, $tableStructure);
            foreach ($jsonObj->semanticSegments as $eachSegment) {
                $tableStructure = $this->buildTableStructure($eachSegment, $tableStructure);
            }// end foreach; `semanticSegments` property.
            unset($eachSegment);
            unset($jsonObj);
        }// endforeach; list files.
        unset($file, $JSONFiles);

        $tableStructureClone = unserialize(serialize($tableStructure));// for deep clone. if use `clone` the sub object of cloned one will be use from original object.
        $tableStructureFlat = $this->flattenTableStructure($tableStructureClone, $tableStructureFlat);
        $output->writeln('  Table structure based on JSON: =============');
        print_r($tableStructure);
        $output->writeln('  End table structure based on JSON: =========');

        $output->writeln('  Flatten: ===================================');
        print_r($tableStructureFlat);
        $output->writeln('  End flatten: ===============================');

        $output->writeln('  Checking structure with the database: ======');
        $this->checkDB($tableStructureFlat);
        $output->writeln('  End check structurewith the database: ======');

        unset($tableStructure, $tableStructureClone, $tableStructureFlat);

        $Db->disconnect();
        unset($Db, $jsonFile, $jsonFolder);

        $output->writeln('Finish.');

        return Command::SUCCESS;
    }// execute


    /**
     * Flatten first level of structure.
     *
     * @param \stdClass $tableStructure
     * @param \stdClass $tableStructureFlat
     * @return \stdClass
     */
    private function flattenLevel1(\stdClass $tableStructure, \stdClass $tableStructureFlat): \stdClass
    {
        foreach ($tableStructure as $tableName => $data) {
            if (!property_exists($tableStructureFlat, $tableName)) {
                $tableStructureFlat->{$tableName} = new \stdClass();
            }

            if (is_object($data)) {
                $tableStructureFlat->{$tableName} = $this->flattenLevelData($data, $tableStructureFlat->{$tableName});
            } elseif (is_array($data)) {
                // if data is array (this may be `timelinePath` property).
                foreach ($data as $row) {
                    if (is_object($row)) {
                        $tableStructureFlat->{$tableName} = $this->flattenLevelData($row, $tableStructureFlat->{$tableName});
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
    private function flattenLevelData(\stdClass $object, \stdClass $tableName): \stdClass
    {
        foreach ($object as $property => $value) {
            if ($this->isDBValueDataTypes($value)) {
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
    private function flattenLevelObject(\stdClass $object, array $results = [], string $prefix = ''): array
    {
        foreach ($object as $property => $value) {
            if (is_object($value) && !empty((array) $value)) {
                if (!empty($prefix) && !empty($property)) {
                    $prefix .= '_';
                }
                $results = $this->flattenLevelObject($value, $results, $prefix . $property);
            } elseif ($this->isDBValueDataTypes($value)) {
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
    private function flattenLevelObjectSubArrayToNewTable(array $arrayVals, \stdClass $tableStructureFlat, string $tableName): \stdClass
    {
        if (!property_exists($tableStructureFlat, $tableName)) {
            $tableStructureFlat->{$tableName} = new \stdClass();
        }

        foreach ($arrayVals as $eachArray) {
            if (is_object($eachArray) && !empty((array) $eachArray)) {
                $columns = $this->flattenLevelObject($eachArray);
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
    private function flattenTableStructure(\stdClass $tableStructure, \stdClass $tableStructureFlat): \stdClass
    {
        $tableStructureFlat = $this->flattenLevel1($tableStructure, $tableStructureFlat);

        foreach ($tableStructure as $tableName => $data) {
            if (is_object($data) && !empty((array) $data)) {
                $columns = $this->flattenLevelObject($data);
                foreach ($columns as $name => $value) {
                    if (!is_array($value)) {
                        $tableStructureFlat->{$tableName}->{$name} = $value;
                    } elseif (is_array($value)) {
                        $tableStructureFlat = $this->flattenLevelObjectSubArrayToNewTable($value, $tableStructureFlat, $tableName . '_' . $name);
                    }
                }// endforeach;
                unset($name, $value);
                unset($columns);
            }// endif;
        }// endforeach;
        unset($data, $tableName);

        return $tableStructureFlat;
    }// flattenTableStructure


    /**
     * Check if variable is matched data type for DB value.
     *
     * @param mixed $variable
     * @return bool
     */
    private function isDBValueDataTypes($variable): bool
    {
        if (is_scalar($variable) || is_null($variable)) {
            return true;
        }
        return false;
    }// isDBValueDataTypes


}
