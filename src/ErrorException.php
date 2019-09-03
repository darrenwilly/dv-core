<?php
namespace DV ;

use ErrorException as parentException ;
use Throwable;

class ErrorException extends parentException
{
    use TraitExceptionBase ;

    public function __construct($message = "", $code = 500, Throwable $previous = null)
    {
        $finalMessage = $this->processMessage($message) ;
        ##
        parent::__construct($finalMessage, $code, 1 , __FILE__ , __LINE__ , $previous);
    }
}