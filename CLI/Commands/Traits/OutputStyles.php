<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace PMTL\CLI\Commands\Traits;


use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Output styles trait.
 */
trait OutputStyles
{


    /**
     * Define output style.
     * 
     * @link https://symfony.com/doc/current/console/coloring.html Document.
     */
    protected function defineOutputStyle(OutputInterface $output)
    {
        $outputStyleSuccess = new OutputFormatterStyle('green');
        $output->getFormatter()->setStyle('success', $outputStyleSuccess);
        unset($outputStyleSuccess);

        $outputStyleWarning = new OutputFormatterStyle('yellow');
        $output->getFormatter()->setStyle('warning', $outputStyleWarning);
        unset($outputStyleWarning);
    }// defineOutputStyle


}
