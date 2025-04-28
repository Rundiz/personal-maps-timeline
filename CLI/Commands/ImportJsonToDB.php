<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace PMTL\CLI\Commands;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Import Google timeline JSON file to DB command.
 */
class ImportJsonToDB extends Command
{


    use Traits\OutputStyles;


    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setName('import-json-to-db');
        $this->setDescription('Import Google timeline JSON to DB.');
        $this->setAliases(['importjsontodb']);
        $this->setProcessTitle('Import Google timeline JSON to DB.');
    }// configure


    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->defineOutputStyle($output);

        $ImportJsonToDb = new Tasks\ImportJsonToDB();
        $ImportJsonToDb->run($input, $output);
        unset($ImportJsonToDb);

        return Command::SUCCESS;
    }// execute


}
