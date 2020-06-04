<?php
namespace DV\Validator ;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator as Laminas_Validate ;


class Percentage extends Laminas_Validate
{
    const NOT_MATCH = 'notMatch';

    protected $messageTemplates = array(
        self::NOT_MATCH => 'The Value provided is not lesser/greater than approved percentage(100%)'
    );

    
    
    public function validate($value, Constraint $constraint)
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