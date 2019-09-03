<?php
namespace DV\Model;

use DV\RuntimeException as parentException;

class RuntimeException extends parentException
{
    protected $logicIdentifier = 'DV_Model' ;
}