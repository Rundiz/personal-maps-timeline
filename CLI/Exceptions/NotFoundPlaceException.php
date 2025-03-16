<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace PMTL\CLI\Exceptions;


/**
 * Not found place exception.
 */
class NotFoundPlaceException extends \Exception
{


    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        return parent::__construct($message, $code, $previous);
    }// __construct


}
