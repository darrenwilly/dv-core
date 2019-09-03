<?php
namespace DV\Validator ;

use Zend\Validator\AbstractValidator as Zend_Validate ;


class Percentage extends Zend_Validate
{
    const NOT_MATCH = 'notMatch';

    protected $messageTemplates = array(
        self::NOT_MATCH => 'The Value provided is not lesser/greater than approved percentage(100%)'
    );

    
    
    public function isValid($value, $context = null)
    {
    	## $value = intval($value) ;
    	
    	$this->setValue($value);
    	
    	### iterate through the number
        if($value <= 100)	{
        	return true ;
        }
        
        $this->error(self::NOT_MATCH);
        return false;
    }
}