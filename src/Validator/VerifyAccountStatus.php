<?php

namespace DV\Validator ;

use Zend\Validator\AbstractValidator as Zend_Validate ;


Class VerifyAccountStatus extends Zend_Validate
{ 
	
	const ACCOUNT_DISABLED = 'accountdisabled' ;
	const ACCOUNT_NON_EXIST = 'accountnotexist' ;
	const ACCOUNT_NOT_ACTIVATED = 'accountnotactivated' ;
	const ACCOUNT_NO_PORTAL_ACCESS = 'accountnoportalaccess' ;
	const UNKWOWN_RESULT = 'unkwownresult' ;

    
    protected $_messageTemplates = array(
        self::ACCOUNT_DISABLED => 'Account "%value%" has been disabled,
        						kindly contact the Darrism Solutions Customer Support' ,
        self::ACCOUNT_NON_EXIST => 'Account "%value%" does not exist or deleted' ,
        self::ACCOUNT_NO_PORTAL_ACCESS => 'Account "%value%" is not enabled for portal access,
        						kindly contact the Darrism Solutions Customer Support' ,
        self::UNKWOWN_RESULT => 'Your account has failed due to unkwown problem' 
    );
    
    
	
	public function __construct($model)
	{
		 $this->_model = $model;
	}
	
	
	
 	public function isValid($value, $context = null)
    {
    	### filter to string
    	$value = (string) $value ;
    	
        $this->_setValue($value);
        
        ### fetch for account supplied by grantee
        $currentUser = $this->_model->getUserbyUsername($value , null , array('Y' , 'N')) ;
        
        ### check for null
        if (null == count($currentUser)) {
        	Veiw_Service_FlashMessenger::flashmessenger('0085') ;
        	$this->_error(self::ACCOUNT_NON_EXIST);
            return false ;
        }   
        
        ### cast the accessLevel value as integer
        $accessLevel = (int) $currentUser->AccessLevel ;
        
        SWITCH(strtolower($currentUser->UserType))	{

        	### allow this check only on grantee account	
        	case 'admin' :
        		
        			SWITCH(true)	{        				
        				case $accessLevel == 1 :
        						return true ;
        					BREAK ;
        					
        				case $accessLevel == 2 : 
        						Veiw_Service_FlashMessenger::flashmessenger('0082') ;
        						$this->_error(self::ACCOUNT_NO_PORTAL_ACCESS) ;
        						return false ;
        					BREAK ;
        					
        				case $accessLevel == 3 : ### suspended
        						Veiw_Service_FlashMessenger::flashmessenger('0083') ;
        						$this->_error(self::ACCOUNT_DISABLED) ;
        						return false ;
        					BREAK ;
        					
        				default :
        					$this->_error(self::UNKWOWN_RESULT) ;
        					return false ;
        					BREAK ;
        			}			        		        
			        
        		break ;
        		
        	default :
        			throw new DV_Model_Exception(DV_Service_SystemMessage::message('0084'), 500) ;
        		break;
        }
        
        
        return true ;
    }
}