<?php


namespace PMTL\Libraries;


/**
 * URL class.
 */
class Url
{


    /**
     * Class constructor.
     */
    public function __construct()
    {
    }// __construct


    /**
     * Get this application base path.
     * 
     * For example: if you install this app on /myapp sub folder then it will return /myapp.<br>
     * If you install this app on root folder then it will return empty string.
     *
     * @return string Return application base path withour trailing slash.
     */
    public function getAppBasePath(): string
    {
        $docRoot = str_replace([DIRECTORY_SEPARATOR, '\\'], '/', $_SERVER['DOCUMENT_ROOT']);
        $appRoot = str_replace([DIRECTORY_SEPARATOR, '\\'], '/', APP_ROOT);
        return rtrim(str_replace($docRoot, '', $appRoot), " \n\r\t\v\0/");
    }// getAppBasePath


}