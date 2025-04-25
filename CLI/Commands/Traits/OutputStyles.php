<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace PMTL\CLI\Commands\Traits;


use Symfony\Component\Console\Formatter\OutputFormatterStyle;


/**
 * Output styles trait.
 */
trait OutputStyles
{


    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $symfonyOutput;


    /**
     * Define output style.
     * 
     * @link https://symfony.com/doc/current/console/coloring.html Document.
     */
    protected function defineOutputStyle()
    {
        $outputStyleSuccess = new OutputFormatterStyle('green');
        $this->symfonyOutput->getFormatter()->setStyle('success', $outputStyleSuccess);
        unset($outputStyleSuccess);

        $outputStyleWarning = new OutputFormatterStyle('yellow');
        $this->symfonyOutput->getFormatter()->setStyle('warning', $outputStyleWarning);
        unset($outputStyleWarning);
    }// defineOutputStyle


}
