<?php


if (strtolower(php_sapi_name()) !== 'cli') {
    throw new \Exception('Please run this file from command line.');
    exit();
}


require 'config.php';
require 'vendor/autoload.php';


set_time_limit(3000000);
ini_set('memory_limit', '2048M');


$RetrievePlaceDetail = new PMTL\CLI\RetrievePlaceDetail();
$RetrievePlaceDetail->run();
unset($RetrievePlaceDetail);
