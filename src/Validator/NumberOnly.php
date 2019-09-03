<?php

namespace DV\Validator ;

use Zend\Validator\AbstractValidator as Zend_Validate ;


class NumberOnly extends Zend_Validate
{ 
	protected $_model ;
	
	
	const INVALID_NUMBER = 'invalidNumber';   
    
    
    protected $_messageTemplates = array(
        self::INVALID_NUMBER => 'a non numeric character detected, please provide only phone/mobile number',
    );

    
    
 	public function isValid($value)
    {
    	
        $this->_setValue($value);

        ### search for other character they might use and replace them with comma
        $value = str_replace(array('.' , ',' , ';' , '/' , '+' , '') , ',' , $value) ;
        ### break the number apart with comma
		$number = explode(',' , $value) ;
		### iterate through the number
		foreach($number AS $_number)	{
			### check for non numeric character
			if(!is_numeric($_number))	{
				 $this->_error(self::INVALID_NUMBER);        
        		return false;
			}
		}

       	return true ;
    }
}