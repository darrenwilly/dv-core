<?php
namespace DV ;

use RuntimeException as parentException ;
use Throwable;

class RuntimeException extends parentException
{
    use TraitExceptionBase;
    public $code ;

    public function __construct($message = "", $code = 500, Throwable $previous = null)
    {
        $finalMessage = $this->processMessage($message) ;
        ##
        parent::__construct($finalMessage, $code, $previous);
    }

    public function setCode($code=500)
    {
        $this->code = $code ;
    }
}