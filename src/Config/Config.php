<?php
declare(strict_types=1);

namespace DV\Config;

use Laminas\Config\Config as parentClass ;

class Config extends parentClass
{
    public function __construct(array $options , bool $allowModifications = true)
    {
        parent::__construct($options , $allowModifications);
    }

    public function __call($name, $arguments)
    {
        if('get' === substr($name , 0 , 3))    {
            ##
            $stripName = strtolower(substr($name , 3 , (strlen($name) - strlen('get')))) ;

            ## check if the property with the striped name exist
            if($this->offsetExists($stripName))    {
                ## condition that check if entire row should be returned
                return $this->get($stripName) ;
            }

            throw new \DV\Exception(sprintf('The magic method called (%s) cannot be interpreted to a valid Object Property(%s) / Settings in %s' , $name , $stripName , get_class($this))) ;
        }
    }
}