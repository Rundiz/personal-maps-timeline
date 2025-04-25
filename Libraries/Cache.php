<?php


namespace PMTL\Libraries;


/**
 * Cache class that will be work on vendor's class.
 * 
 * @property-read \Psr\SimpleCache\CacheInterface $SimpleCache
 */
class Cache
{


    /**
     * @var \Psr\SimpleCache\CacheInterface
     */
    protected $SimpleCache;


    /**
     * Class constructor.
     */
    public function __construct()
    {
        if (function_exists('apcu_fetch')) {
            $this->SimpleCache = new \Rundiz\SimpleCache\Drivers\Apcu();
        } else {
            $this->SimpleCache = new \Rundiz\SimpleCache\Drivers\FileSystem();
        }
    }// __construct


    /**
     * Class magic get.
     * 
     * @param string $name
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
    }// __get


}
