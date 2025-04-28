<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace PMTL\CLI\Commands\Tasks;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;


/**
 * Retrieve place detail task.
 */
class RetrievePlaceDetailTask
{


    /**
     * @property \PMTL\CLI\Commands\RetrievePlaceDetail $Command
     */
    private $Command;


    /**
     * @property \PMTL\Libraries\Db|null $Db
     */
    protected $Db;


    /**
     * @var \PDO|null
     */
    protected $dbh;


    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    private $Input;


    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $Output;


    /**
     * Class constructor.
     * 
     * @param \Symfony\Component\Console\Command\Command $Command
     */
    public function __construct(Command $Command)
    {
        $this->Command = $Command;
        $this->Db = new \PMTL\Libraries\Db();
        $this->dbh = $this->Db->connect();
    }// __construct


    /**
     * Class destructor.
     */
    public function __destruct()
    {
        $this->Db->disconnect();
        $this->Db = null;
        $this->dbh = null;
    }// __destruct


    /**
     * @type array The valid API engines available in the command.
     */
    public const VALID_API_ENGINES = ['google', 'nominatim'];
    
    
    /**
     * Make API call to get Google Place detail.
     * 
     * @link https://developers.google.com/maps/documentation/places/web-service/place-details?hl=en Reference/document.
     * @param string $placeId
     * @return object Return object with property `displayName->text` if place exists.
     * @throws \PMTL\CLI\Commands\Tasks\RetrievePlaceDetailTask\Exceptions\JSONException Throw exception when JSON decode failed.
     * @throws \PMTL\CLI\Commands\Tasks\RetrievePlaceDetailTask\Exceptions\InvalidAPIKeyException Throw exception when invalid API key.
     * @throws \PMTL\CLI\Commands\Tasks\RetrievePlaceDetailTask\Exceptions\ApiDisabledException Throw exception when API is disabled on Google.
     * @throws \PMTL\CLI\Commands\Tasks\RetrievePlaceDetailTask\Exceptions\NotFoundPlaceException Throw exception when not found this place ID on Google.
     * @throw \Exception Throw the most basic exception for another errors.
     */
    private function apiGoogleGetPlaceDetail(string $placeId)
    {
        $ch = curl_init('https://places.googleapis.com/v1/places/' . rawurlencode($placeId) . '?languageCode=th');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Goog-Api-Key: ' . GOOGLE_MAPS_API_KEY,
            'X-Goog-FieldMask: id,displayName',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (defined('CAINFO_FILE') && !empty(CAINFO_FILE)) {
            curl_setopt($ch, CURLOPT_CAINFO, CAINFO_FILE);
        }
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception('cURL Error: ' . curl_error($ch));
        }
        curl_close($ch);
        unset($ch);

        $responseObj = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RetrievePlaceDetailTask\Exceptions\JSONException(json_last_error_msg());
        }
        unset($response);

        if (isset($responseObj->error->code)) {
            // if there is error code.
            if (400 === intval($responseObj->error->code)) {
                $errorMsg = ($responseObj->error->message ?? 'Invalid API key.');
                throw new RetrievePlaceDetailTask\Exceptions\InvalidAPIKeyException($errorMsg);
            } elseif (403 === intval($responseObj->error->code)) {
                $errorMsg = ($responseObj->error->message ?? 'API is disabled.');
                throw new RetrievePlaceDetailTask\Exceptions\ApiDisabledException($errorMsg);
            } elseif (404 === intval($responseObj->error->code)) {
                $errorMsg = ($responseObj->error->message ?? 'Not found this place ID or it is no longer valid.');
                $errorMsg .= ' (status: ' . $responseObj->error->status . ')';
                throw new RetrievePlaceDetailTask\Exceptions\NotFoundPlaceException($errorMsg);
            } else {
                throw new \Exception($responseObj->error->message);
            }
        } else {
            // if something else (maybe no errors at all).
            if (!isset($responseObj->displayName->text)) {
                throw new \Exception('The required response property (displayName->text) is not found.');
            }
        }// endif; there is `error->code`.
        return $responseObj;
    }// apiGoogleGetPlaceDetail


    /**
    * Make API call to reverse geocoding with Nominatim.
    *
    * @link https://nominatim.org/release-docs/develop/api/Reverse/ Reference/documentation.
    * @param float $latitude
    * @param float $longitude
    * @return object Return object with property `displayName->text` if place exists.
     * @throws \PMTL\CLI\Commands\Tasks\RetrievePlaceDetailTask\Exceptions\JSONException Throw exception when JSON decode failed.
     * @throw \Exception Throw the most basic exception for another errors.
    */
    private function apiNominatimOSMGetPlaceDetail(string $latLng)
    {
        [$latitude, $longitude] = explode(',', $latLng);
        $url = 'https://nominatim.openstreetmap.org/reverse?format=json&lat=' . rawurlencode($latitude) . '&lon=' . rawurlencode($longitude);
        if (defined('NOMINATIM_EMAIL') && !empty(NOMINATIM_EMAIL)) {
            $url .= '&email=' . rawurlencode(NOMINATIM_EMAIL);
        }
        $responseHeaders = [];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Personal maps timeline. ( https://github.com/Rundiz/personal-maps-timeline )');
        if (defined('CAINFO_FILE') && !empty(CAINFO_FILE)) {
            curl_setopt($ch, CURLOPT_CAINFO, CAINFO_FILE);
        }
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,
            function($curl, $header) use (&$responseHeaders)
            {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) {
                    // ignore invalid headers
                    return $len;
                }

                $responseHeaders[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception('cURL Error: ' . curl_error($ch));
        }
        curl_close($ch);
        unset($latitude, $longitude, $url);
        unset($ch);

        if (
            !isset($responseHeaders['content-type'][0]) ||
            (
                isset($responseHeaders['content-type'][0]) && 
                is_string($responseHeaders['content-type'][0]) &&
                stripos($responseHeaders['content-type'][0], '/json') === false
            )
        ) {
            // If response has no content type or has one but not application/json.
            // The response result is text or HTML. Throw them as an error.
            throw new \Exception(trim(strip_tags($response)));
        }

        $responseObj = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RetrievePlaceDetailTask\Exceptions\JSONException('JSON Error: ' . json_last_error_msg());
        }
        unset($response, $responseHeaders);

        if (!isset($responseObj->display_name)) {
            throw new \Exception('The required response property (display_name) is not found.');
        }
        // Make the result to be the same as Google place API.
        $responseObj->displayName = new \stdClass();
        $responseObj->displayName->text = $responseObj->display_name;

        return $responseObj;
    }// apiNominatimOSMGetPlaceDetail


    /**
     * Ask for confirmation based on selected API engine.
     * 
     * @param string $APIEngine The API engine.
     * @param InputInterface $Input Symfony input
     * @param OutputInterface $Output Symfony output
     * @return bool Return `true` if confirmed, `false` for not or did not found valid API engine.
     */
    public function confirmAPIEngine(string $APIEngine, InputInterface $Input, OutputInterface $Output): bool
    {
        $Helper = $this->Command->getHelper('question');

        if ('google' === $APIEngine) {
            $question = 'This will be retrieve place details from Google API and update to the DB. It may cost your money.' . PHP_EOL
                . 'It will be retrieve 1000 places per request.' . PHP_EOL
                . 'Are you sure to continue? [y/n]';
            $Question = new ConfirmationQuestion($question, false);
            unset($question);

            if (!$Helper->ask($Input, $Output, $Question)) {
                return false;
            }
            return true;
        } elseif ('nominatim' === $APIEngine) {
            $question = 'This will be retrieve place details using Nominatim reverse geocoding and update to the DB.' . PHP_EOL
                . 'It will be retrieve 1000 places per request.' . PHP_EOL
                . 'Are you sure to continue? [y/n]';
            $Question = new ConfirmationQuestion($question, false);
            unset($question);

            if (!$Helper->ask($Input, $Output, $Question)) {
                return false;
            }
            return true;
        }// endif; detect API engine choice to ask for confirm.

        return false;
    }// confirmAPIEngine


    /**
     * Display error message in error style to the console.
     * 
     * @param string $message The error message.
     */
    private function displayError(string $message)
    {
        $this->Output->writeln(' <error>Error!</error>');
        $this->Output->writeln('  <error>' . $message . '</error>');
    }// displayError


    /**
     * Doing fetch place name and then update.
     * 
     * @param array $result
     */
    private function doUpdate(array $result)
    {
        if ($result) {
            $updated = 0;
            foreach ($result as $row) {
                $this->Output->write('Place ID: ' . $row->topCandidate_placeId);
                $this->Output->write(' (' . $row->countPlaceId . ')');
                $this->Output->writeln('');
                // Remove anything that is not [number, dot, minus, comma] sign in the latitude and longitude string.
                $latLngForURL = preg_replace('/[^\d\.\-\,]/', '', $row->topCandidate_placeLocation_latLng);
                // Display URL for check with Google Maps website.
                $gMapsURL = 'https://www.google.com/maps/search/?api=1'
                    . '&query=' . rawurlencode($latLngForURL) 
                    . '&query_place_id=' . rawurlencode($row->topCandidate_placeId);
                $this->Output->writeln('  Check on Google Maps: <href="' . $gMapsURL . '">' . $gMapsURL . '</>');
                unset($gMapsURL);

                $breakLoop = false;
                try {
                    if ('google' === $this->Command->APIEngine) {
                        $placeObj = $this->apiGoogleGetPlaceDetail($row->topCandidate_placeId);
                    } elseif ('nominatim' === $this->Command->APIEngine) {
                        $placeObj = $this->apiNominatimOSMGetPlaceDetail($latLngForURL);
                    }// endif; API engine.
                } catch (Exceptions\JSONException $ex) {
                    $this->displayError($ex->getMessage());
                } catch (Exceptions\InvalidAPIKeyException $ex) {
                    $this->displayError($ex->getMessage());
                    // No more loop after insert/update.
                    $breakLoop = true;
                } catch (Exceptions\ApiDisabledException $ex) {
                    $this->displayError($ex->getMessage());
                    // No more loop after insert/update.
                    $breakLoop = true;
                } catch (Exceptions\NotFoundPlaceException $ex) {
                    $this->displayError($ex->getMessage());
                } catch (\Exception $ex) {
                    $this->displayError($ex->getMessage());
                }// endtry;

                $placeName = null;
                if (isset($placeObj->displayName->text) && is_string($placeObj->displayName->text)) {
                    $placeName = trim($placeObj->displayName->text);
                    $this->Output->write('  "' . $placeName . '"');
                }// endif; there is place details or not.

                // Save data to the database. ---------------------------------------------------
                $sql = 'INSERT INTO `google_places`  (`place_id`, `place_name`, `last_update`) VALUES (:place_id, :place_name, :last_update)
                    ON DUPLICATE KEY UPDATE `place_name` = :place_name';
                $Sth = $this->dbh->prepare($sql);
                unset($sql);
                $Sth->bindValue(':place_id', trim($row->topCandidate_placeId));
                $Sth->bindValue(':place_name', $placeName);
                $Sth->bindValue(':last_update', date('Y-m-d H:i:s'));
                $Sth->execute();
                $insertId = $this->dbh->lastInsertId();
                $Sth->closeCursor();
                unset($Sth);
                if (false !== $insertId) {
                    ++$updated;
                    $this->Output->write('  :: <fg=black;bg=green>inserted/updated</>');
                    if (is_null($placeName)) {
                        $this->Output->write(' (as `NULL`)');
                    }
                }
                unset($insertId);
                // End save data to the database. ----------------------------------------------

                $this->Output->writeln('');
                $this->Output->writeln('');
                unset($placeName, $placeObj);
                unset($latLngForURL);

                if (true === $breakLoop) {
                    break;
                }
            }// endforeach;
            unset($row);
            unset($breakLoop);

            $this->Output->writeln('Total ' . count($result) . ' rows found, inserted/updated ' . $updated . ' rows.');
            unset($updated);
        } else {
            $this->Output->writeln('Total 0 rows found. Nothing to retrieve.');
        }
    }// doUpdate


    /**
     * Get the places that has no name or too old and need update.
     * 
     * @return array Return array from `PDO` `fetchAll()`.
     */
    private function getPlacesNeedUpdate(): array
    {
        $sql = 'SELECT `visit_id`, `topCandidate_placeId`, `topCandidate_placeLocation_latLng`, COUNT(`topCandidate_placeId`) AS `countPlaceId`,
        `google_places`.`place_name`, `google_places`.`last_update`
        FROM `visit`
        LEFT JOIN `google_places` ON `visit`.`topCandidate_placeId` = `google_places`.`place_id`
        WHERE (`place_name` IS NULL AND `last_update` IS NULL)
            OR (`place_name` IS NULL AND `last_update` <= NOW() - INTERVAL 1 YEAR)
        GROUP BY `topCandidate_placeId`
        ORDER BY `countPlaceId` DESC
        LIMIT 1000 OFFSET 0';
        $Sth = $this->dbh->prepare($sql);
        unset($sql);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($Sth);

        if (is_array($result)) {
            return $result;
        }
        return [];
    }// getPlacesNeedUpdate


    /**
     * Run the task.
     * 
     * @param InputInterface $Input Symfony input
     * @param OutputInterface $Output Symfony output
     */
    public function run(InputInterface $Input, OutputInterface $Output)
    {
        $this->Input = $Input;
        $this->Output = $Output;

        $result = $this->getPlacesNeedUpdate();
        $this->doUpdate($result);
        unset($result);
    }// run


}
