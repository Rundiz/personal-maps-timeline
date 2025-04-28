<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace PMTL\CLI\Commands;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Check DB structure command.
 */
class CheckDBStructure extends Command
{


    use Traits\OutputStyles;


    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setName('check-db-structure');
        $this->setDescription('Check for data structure in JSON but not exists on DB.');
        $this->setAliases(['checkdbstructure']);
    }// configure


    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->defineOutputStyle($output);

        $CheckDBStructure = new Tasks\CheckDBStructure();
        $CheckDBStructure->run($input, $output);
        unset($CheckDBStructure);

        return Command::SUCCESS;
    }// execute


}
