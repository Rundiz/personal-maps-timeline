<?php


if (strtolower(php_sapi_name()) !== 'cli') {
    throw new \Exception('Please run this file from command line.');
    exit();
}


require 'config.php';
require 'vendor/autoload.php';


$Db = new \PMTL\Libraries\Db();
$dbh = $Db->connect();


echo 'Are you sure to clear all location DB data? [y/n]';
$confirmation = strtolower(trim(fgets(STDIN)));
if ($confirmation !=='y') {
   // The user did not say 'y'.
   echo 'Cancelled.';
   exit(1);
}


$dbh->query('TRUNCATE `activity`');
$dbh->query('TRUNCATE `semanticsegments`');
$dbh->query('TRUNCATE `timelinememory`');
$dbh->query('TRUNCATE `timelinememory_trip_destinations`');
$dbh->query('TRUNCATE `timelinepath`');
$dbh->query('TRUNCATE `visit`');


$Db->disconnect();
unset($Db, $dbh);

echo 'All data cleared.' . PHP_EOL;