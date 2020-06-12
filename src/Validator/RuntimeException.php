<?php
namespace DV\Validator ;

use DV\TraitExceptionBase;
use RuntimeException as parentException ;
use Throwable;

class RuntimeException extends parentException
{
    use TraitExceptionBase;
    protected $logicIdentifier = __CLASS__ ;
    /**
     * RuntimeException constructor.
     * Please note that any validator that throw this exception should be attended to, bcos the chances are very thin that a validator will throw an exception
     * Therefore, this exception should mail me any immediately
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 500, Throwable $previous = null)
    {
        $finalMessage = $this->processMessage($message) ;
        ##
        parent::__construct($finalMessage, $code, $previous);
    }
}