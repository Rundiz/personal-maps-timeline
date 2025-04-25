<?php
/**
 * Warning! This file will be deleted in the future.  
 * Use this command instead: `php pmtl.php import-json-to-db`.
 * @since 2025-04-25
 * @deprecated since 2025-04-25
 */


if (strtolower(php_sapi_name()) !== 'cli') {
    throw new \Exception('Please run this file from command line.');
    exit();
}


require 'config.php';
require 'vendor/autoload.php';


if (!defined('PMTL_MEMORY_LIMIT')) {
    define('PMTL_MEMORY_LIMIT', '1G');
}
ini_set('memory_limit', PMTL_MEMORY_LIMIT);


// start process. ==================================

echo 'Warning! This file will be deleted in the future.' . PHP_EOL
    . 'Please use this command instead: `php pmtl.php import-json-to-db`.' . PHP_EOL . PHP_EOL;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;


$MainEntry = new \PMTL\CLI\MainEntry();
$Application = $MainEntry->Application;
$Application->setAutoExit(false);
unset($MainEntry);

$input = new ArrayInput([
    'command' => 'import-json-to-db',
]);
$output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);

$Application->run($input, $output);
echo $output->fetch();

unset($input, $output);
unset($Application, $converter);
