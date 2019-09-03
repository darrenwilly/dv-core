<?php

namespace DV\Validator ;

use Zend\Validator\AbstractValidator as Zend_Validate ;


class UniqueFilename extends Zend_Validate
{ 
	
	protected $_model ;

	
	const FILENAME_EXISTS = 'emailExists';

    
    protected $_messageTemplates = array(
        self::FILENAME_EXISTS => 'Current Filename "%value%" already exists in our system',
    );

    
    public function __construct($model)
    {
        $this->_model = $model;
    }
    
    
 	public function isValid($value, $context = null)
    {
    	$value = (string) $value ;
    	
     	/*if (isset($context['DocType']))
        {
        	#### replace all space with underscore
             $value = trim(str_replace(' ' , '_' , $value)) ;
            #### join the DocType Content with Filename Value.
            $value = $value . '_' . trim($context['DocType']) ;
        }*/
        ### set the method value    
        $this->_setValue($value);

        $this->_error(self::FILENAME_EXISTS);
        
        return false;
    }
}