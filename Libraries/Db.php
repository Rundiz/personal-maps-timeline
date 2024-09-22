<?php


namespace PMTL\Libraries;


/**
 * Database class that is working with PDO.
 */
class Db
{


    /**
     * @var null|\PDO The PDO instance
     */
    protected $PDO;


    /**
     * DB class constructor.
     */
    public function __construct()
    {
        $this->connect();
    }// __construct


    /**
     * DB class destructor.
     */
    public function __destruct()
    {
        $this->disconnect();
    }// __destruct


    /**
     * Connect the database.
     *
     * @return null|\PDO Return `\PDO` if create new instance successfully. Return `null` for otherwise.
     */
    public function connect(): ?\PDO
    {
        if ($this->PDO instanceof \PDO) {
            return $this->PDO;
        }

        if (!defined('DB_CHARSET')) {
            define('DB_CHARSET', 'utf8mb4');
        }

        $dsn = 'mysql:dbname=' . DB_NAME . ';host=' . DB_HOST . ';charset=' . DB_CHARSET;
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_STRINGIFY_FETCHES => true,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
        ];
        $this->PDO = new \PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
        unset($dsn, $options);

        return $this->PDO;
    }// connect


    /**
     * Disconnect the database.
     *
     * @return void
     */
    public function disconnect()
    {
        $this->PDO = null;
    }// disconnect


}