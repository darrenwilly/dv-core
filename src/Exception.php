<?php
namespace DV ;

use Exception as parentException ;
use Throwable;

class Exception extends parentException
{
    use TraitExceptionBase ;

    public function __construct($message = "", $code = 500, Throwable $previous = null)
    {
        $finalMessage = $this->processMessage($message) ;
        ##
        parent::__construct($finalMessage, $code, $previous);
    }
}