<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace PMTL\CLI\Commands;


use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;


/**
 * Clear the database class.
 */
class ClearDB extends Command
{


    use Traits\OutputStyles;


    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setName('clear-db');
        $this->setDescription('Clear all the data on database.');
        $this->setAliases(['cleardb']);
    }// configure


    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->symfonyOutput = $output;
        $this->defineOutputStyle();

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure to clear all location DB data? [y/n]', false);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('Cancelled.');
            return Command::SUCCESS;
        }

        // if user confirmed.
        $Db = new \PMTL\Libraries\Db();
        $dbh = $Db->connect();

        $dbh->query('TRUNCATE `activity`');
        // will NOT truncate `google_places` table. retrieve data from Google maps costs money.
        $dbh->query('TRUNCATE `semanticsegments`');
        $dbh->query('TRUNCATE `timelinememory`');
        $dbh->query('TRUNCATE `timelinememory_trip_destinations`');
        $dbh->query('TRUNCATE `timelinepath`');
        $dbh->query('TRUNCATE `visit`');

        $Db->disconnect();
        unset($Db, $dbh);

        $output->writeln('<success>All data cleared.</success>');
        
        return Command::SUCCESS;
    }// execute


}
