<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace PMTL\CLI\Commands\Tasks\RetrievePlaceDetailTask\Exceptions;


/**
 * API disabled exception.
 */
class ApiDisabledException extends \Exception
{


    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        return parent::__construct($message, $code, $previous);
    }// __construct


}
