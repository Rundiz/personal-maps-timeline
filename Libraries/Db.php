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
        try {
            $this->PDO = new \PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
            unset($dsn, $options);
        } catch (\Exception $ex) {
            throw $ex;
            exit(1);
        }

        return $this->PDO;
    }// connect


    /**
     * Bind multiple values.
     * 
     * @param \PDOStatement $Sth The PDO statement class.
     * @param array $bindValues Bind values associative array. The key is `param`, and accepted `value` and `type` as sub keys. Example:<pre>
     *      array(
     *          ':year' => array(
     *              'value' => 2025,
     *              'type' => \PDO::PARAM_INT,
     *          ),
     *          ':date' => array(
     *              'value' => '2025-01-20',
     *          ),
     *      )
     * </pre>
     */
    public function bindValues(\PDOStatement $Sth, array $bindValues)
    {
        foreach ($bindValues as $param => $item) {
            if (isset($item['type'])) {
                $Sth->bindValue($param, $item['value'], $item['type']);
            } else {
                $Sth->bindValue($param, $item['value']);
            }
        }// endforeach;
        unset($item, $param);
    }// bindValues


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