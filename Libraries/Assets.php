<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace PMTL\Libraries;


/**
 * Assets class.
 */
class Assets
{


    /**
     * @var \PMTL\Libraries\Url
     */
    private $Url;


    /**
     * Class constructor.
     * 
     * @param \PMTL\Libraries\Url $Url The URL class.
     */
    public function __construct(Url $Url)
    {
        $this->Url = $Url;
    }// __construct


    /**
     * Reformat asset URL with version query string appended. By default it will be use file modify time as version query string.
     * 
     * @param string $assetUrl The relative path of asset URL refer from this app's root.
     * @param array $options Accepted options:<br>
     *              `v` The manual set of version query string.
     * @return string Return asset URL and check if file exists then the version query string will be appended, otherwise return the same value as `$assetUrl`.
     */
    public function assetUrl(string $assetUrl, array $options = []): string
    {
        if (stripos($assetUrl, '://') !== false || stripos($assetUrl, '//') === 0) {
            // if asset url is full URL then no need to get file mtime.
            return $assetUrl;
        }

        $assetUrl = ltrim($assetUrl, " \n\r\t\v\x00\\/");
        $urlParsed = parse_url($assetUrl);
        $url = (isset($urlParsed['path']) ? $urlParsed['path'] : '');
        $query = (isset($urlParsed['query']) ? $urlParsed['query'] : '');
        unset($urlParsed);

        if (array_key_exists('v', $options) && is_scalar($options['v'])) {
            $queryArray = [];
            $queryArray['manual-v'] = $options['v'];
        }

        if (!isset($queryArray) || empty($queryArray)) {
            // if query array of assets never generated before (not set).
            parse_str($query, $queryArray);
            unset($query);

            if (isset($queryArray['v'])) {
                $additionalQueryName = 'v' . time();
            } else {
                $additionalQueryName = 'v';
            }

            $assetFullPath = realpath(dirname(__DIR__) . '/' . $url);
            if (is_string($assetFullPath) && filesize($assetFullPath) <= 1048576) {
                // if file size is smaller than or equal to 1 MB.
                // use md5 file.
                $additionalQueryValue = md5_file($assetFullPath);
            }

            if (
                (
                    !isset($additionalQueryValue) || 
                    false === $additionalQueryValue
                ) && 
                is_string($assetFullPath)
            ) {
                // if additional query string value was not set.
                // use file modify time.
                $additionalQueryValue = filemtime($assetFullPath);
            }

            if (!isset($additionalQueryValue) || false === $additionalQueryValue) {
                // if additional query string value still not set.
                // use current timestamp.
                $additionalQueryValue = 'curtime-' . time();
            }

            $queryArray[$additionalQueryName] = $additionalQueryValue;
            unset($additionalQueryName, $additionalQueryValue, $assetFullPath);
        }// endif; query array of assets never generated before.

        $query = http_build_query($queryArray, '', '&amp;');
        unset($queryArray);

        return $this->Url->getAppBasePath() . '/' . $url . '?' . $query;
    }// assetUrl


}
