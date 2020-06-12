<?php

namespace DV\Validator ;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MatchPreviousPwd extends ConstraintValidator
{
    const NOT_MATCH = 'notMatch';

    protected $_messageTemplates = array(
        self::NOT_MATCH => 'Passwords do not match the previous.'
    );

    protected $_model ;
    
    protected $_userId ;
    
    
    public function validate($value, Constraint $constraint)
    {
    	//validate the value as string
        //$value = (string) $value;
        //set the value.
        $this->_setValue($value);
        
        //pass the user Id back from object to int.
        $userId = $this->_userId ;
		//fetch the user by his Id
        $user = $this->_model->getUserbyId($userId) ;
        //check for null returns.
        if(null == $user)	{
        	return false ;
        }
        
        //preparing Old password as Hash.
        $oldPwd = SHA1($value . $user->Salt) ;
        //checking if the Element value is equal to the user previous pwd object .
        if($user->Pwd === $oldPwd)	{
        	return true ;
        }
        
        $this->_error(self::NOT_MATCH);
        return false;
    }
}