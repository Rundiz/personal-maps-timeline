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

// Google Maps place API (new) config.
define('GOOGLE_MAPS_API_KEY', '');

// set your timezone (refer from https://www.php.net/manual/en/timezones.php ).
date_default_timezone_set('Asia/Bangkok');


// ==================================================================
// Do not edit line below this.
define('APP_ROOT', __DIR__);