<?php

namespace DV\Validator ;

use Zend\Validator\AbstractValidator as Zend_Validate ;


class UniqueMobile extends Zend_Validate
{ 
	protected $_model ;
	
	const MOBILE_EXISTS = 'mobileExists';

    
    protected $_messageTemplates = array(
        self::MOBILE_EXISTS => 'Current Mobile/Telephone number "%value%" already exists in our system',
    );

    
    public function __construct(DV_Model_Abstract $model )
    {
    	if($model != null)	{
    		$this->_model = $model ;
    	}
    }
      
    
 	public function isValid($value, $context = null)
    {
    	
        $this->_setValue($value);

        ## fetch the documentname and assign it to the varialbe
		$mobile = $this->_model->getUserProfilebyTelephone($value)	;  

		### check for null.
        if (null == count($mobile)) {
            return true;
        }

        $this->_error(self::MOBILE_EXISTS);
        
        return false;
    }
}