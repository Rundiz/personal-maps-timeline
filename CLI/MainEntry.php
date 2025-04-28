<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace PMTL\CLI;


/**
 * Main entry for CLI.
 * 
 * @property-read \Symfony\Component\Console\Application $Application
 */
class MainEntry
{


    /**
     * @var \Symfony\Component\Console\Application
     */
    protected $Application;


    /**
     * Main entry class constructor.
     */
    public function __construct()
    {
        $this->Application = new \Symfony\Component\Console\Application('Personal maps timeline');
        $this->registerCommands();
    }// __construct


    /**
     * Magic get.
     * 
     * @param string $name The property name.
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
    }// __get


    /**
     * Run the command.
     */
    public function run()
    {
        $this->Application->run();
    }// run


    /**
     * Register Symfony console commands.
     * 
     * This method was called from class constructor.
     */
    protected function registerCommands()
    {
        $this->Application->add(new Commands\CheckDBStructure());
        $this->Application->add(new Commands\ClearDB());
        $this->Application->add(new Commands\ImportJsonToDB());
        $this->Application->add(new Commands\RetrievePlaceDetail());
    }// registerCommands


}
