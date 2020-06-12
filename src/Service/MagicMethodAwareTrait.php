<?php
namespace DV\Service;

use Laminas\Config\Config;
use Laminas\Config\Reader\Json;
use Laminas\Filter\Word\CamelCaseToUnderscore;

trait MagicMethodAwareTrait
{

    public function __call($name, $arguments)
    {
        ##
        if('get' === substr($name , 0 , 3))    {
            ##
            $stripName = substr($name , 3 , (strlen($name) - strlen('get')) ) ;
            ##
            $filter = new CamelCaseToUnderscore() ;
            ##
            $propertyToLookFor = strtoupper($filter->filter($stripName));

            ## check if the property with the striped name exist
            if(property_exists($this , $propertyToLookFor))    {
                ## condition that check if entire row should be returned
                if(isset($arguments['get-entity-row']))    {
                    ## return the row itself;
                    return $this->getInfo(self::${$propertyToLookFor});
                }

                ## condition that check if the Datatype Column should be returned or the row
                if(isset($arguments['datatype']))    {
                    ## return the row itself;
                    return $this->getInfo(self::${$propertyToLookFor})->getDatatype();
                }

                ## fetch the datatype of the GroupSettings to know if manipulation should be applied
                $datatype = $this->getInfo(self::${$propertyToLookFor})->getDatatype();
                ##
                if(strtolower($datatype) == 'json')    {
                    ##
                    return new Config((new Json())->fromString($this->getInfo(self::${$propertyToLookFor})->getGroupSettings())) ;
                }
                ## condition that check if the GroupSettings Column should be returned or the row
                return $this->getInfo(self::${$propertyToLookFor})->getGroupSettings() ;
            }

            throw new \DV\Exception(sprintf('The magic method called (%s) cannot be interpreted to a valid Object Property(%s) / Settings in %s' , $name , $propertyToLookFor , get_class($this))) ;
        }
    }
}