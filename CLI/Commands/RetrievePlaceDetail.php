<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace PMTL\CLI\Commands;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use PMTL\CLI\Commands\Tasks\RetrievePlaceDetailTask;


/**
 * Retrieve place detail command.
 * 
 * @property-read string|null $APIEngine The selected API engine from user's choice.
 */
class RetrievePlaceDetail extends Command
{


    use Traits\OutputStyles;


    /**
     * @var null|string The selected API engine from user's choice. This property will be set in `interact()` method if answer is valid.
     */
    private $APIEngine;


    /**
     * @var bool Confirm API engine status.
     */
    private $confirmedAPIEngine = false;


    /**
     * @var string Accept API engine choice string to display in the command.
     */
    private $commandAcceptAPIEngineString = '';


    public function __construct(?string $name = null)
    {
        $this->commandAcceptAPIEngineString = '\'' . implode('\', \'', RetrievePlaceDetailTask::VALID_API_ENGINES) . '\'';

        parent::__construct($name);
    }// __construct


    /**
     * Magic get
     * 
     * @param string $name
     */
    public function __get(string $name)
    {
        if ('APIEngine' === $name) {
            return $this->APIEngine;
        }
    }// __get


    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setName('retrieve-place-detail');
        $this->setDescription('Retrieve place detail from external service such as Google Place, Nominatim.');
        $this->setAliases(['retrieveplacedetail']);
        $this->addArgument('api_engine', InputArgument::REQUIRED, 'What API engine to use? Accept ' . $this->commandAcceptAPIEngineString . '.');
    }// configure


    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->defineOutputStyle($output);

        if (!$this->APIEngine) {
            // if invalid API engine.
            return Command::INVALID;
        }
        if (true !== $this->confirmedAPIEngine) {
            // if user did not confirmed continue.
            return Command::SUCCESS;
        }

        $RetrievePlaceDetailTask = new RetrievePlaceDetailTask($this);
        $RetrievePlaceDetailTask->run($input, $output);
        unset($RetrievePlaceDetailTask);

        return Command::SUCCESS;
    }// execute


    /**
     * {@inheritDoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $APIEngine = $input->getArgument('api_engine');
        if (!in_array($APIEngine, RetrievePlaceDetailTask::VALID_API_ENGINES)) {
            $output->writeln('<error>Please enter the correct API engine. Accepted ' . $this->commandAcceptAPIEngineString . '.</error>');
            $APIEngine = null;
            $this->APIEngine = null;
        } else {
            $this->APIEngine = $APIEngine;
        }// endif; check valid api_engine argument.

        if (is_string($APIEngine)) {
            $RetrievePlaceDetailTask = new RetrievePlaceDetailTask($this);
            $this->confirmedAPIEngine = $RetrievePlaceDetailTask->confirmAPIEngine($APIEngine, $input, $output);
            unset($RetrievePlaceDetailTask);

            if (false === $this->confirmedAPIEngine) {
                $output->writeln('You have canceled.');
            }
        }
        unset($APIEngine);
    }// interact


}
