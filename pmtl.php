<?php
/**
 * Run command `php pmtl.php list` to see all available commands.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */


if (strtolower(php_sapi_name()) !== 'cli') {
    throw new \Exception('Please run this file from command line.');
    exit();
}


require 'config.php';
require 'vendor/autoload.php';


// @link https://www.php.net/manual/en/info.configuration.php#ini.max-execution-time PHP CLI is already unlimited execution timeout by default.
if (!defined('PMTL_MEMORY_LIMIT')) {
    define('PMTL_MEMORY_LIMIT', '1G');
}
ini_set('memory_limit', PMTL_MEMORY_LIMIT);


$MainEntry = new PMTL\CLI\MainEntry();
$MainEntry->run();
unset($MainEntry);