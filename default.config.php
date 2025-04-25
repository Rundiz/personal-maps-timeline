<?php
/**
 * Personal maps timeline configuration file.
 * 
 * This file should stay at the app's root (same level as index.php and import-json-to-db.php files).
 * 
 * @author Vee W.
 * @license MIT
 * @package personal maps timeline
 */


// DB config.
define('DB_NAME', '');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8mb4');
define('DB_USERNAME', '');
define('DB_PASSWORD', '');

// JSON exported files config.
$jsonFolder = '';// path to your folder that contain exported Google Maps timeline JSON files.
$jsonFile = null;// set to `null` to use all files.

// Google Maps place API key.
// This is for use with some features that require Google API such as retrieve place detail from Google Maps based on its place ID.
// You can leave this empty.
define('GOOGLE_MAPS_API_KEY', '');

// If you are making large numbers of request to Nominatim please include an appropriate email address. ( Read more at https://nominatim.org/release-docs/develop/api/Reverse/#other )
define('NOMINATIM_EMAIL', '');

// Set your timezone (refer from https://www.php.net/manual/en/timezones.php ).
date_default_timezone_set('Asia/Bangkok');

// Full path to Certificate Authority (CA) Info file (.pem).
// If you have problem with SSL, then download .pem file from https://curl.se/docs/caextract.html and set the full path in this constant value.
define('CAINFO_FILE', '');

// Set maximum memory limit for PHP. ( See https://www.php.net/manual/en/ini.core.php#ini.memory-limit )
define('PMTL_MEMORY_LIMIT', '1G');


// ==================================================================
// Do not edit line below this.
define('APP_ROOT', __DIR__);