<?php
namespace DV\Otp;

use DV\RuntimeException as parentException;

class RuntimeException extends parentException
{
    protected $logicIdentifier = 'DV_Otp' ;
}