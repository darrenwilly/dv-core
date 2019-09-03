<?php

namespace DV\InputFilter ;

use Zend\InputFilter\InputFilter as FormInputFilter;
use Zend\InputFilter\Input as ElementInput;
use Zend\Validator as FormValidator;
use Zend\Filter as FormFilter;



class Login extends FormInputFilter
{
    protected $_filter ;
    
    protected $_validator ;
    
    protected $_inputFilter ;
    
    protected $_input ;
    
    
    	public function __construct()
    	{    	     	    	
    	    
    	    if(null == $this->_filter)	{
    	        $this->_filter = new FormFilter() ;
    	    }
    	    
    	    $email = new ElementInput('Username');
    	    $email->getValidatorChain()
    	    			->attach(new FormValidator\EmailAddress()) ;
    	    $email->getFilterChain()
    	    				->attachByName('stringtrim')
    	    				->attachByName('alpha');
    	    
    	    
    	    $password = new ElementInput('Pwd');
    	    $password->getValidatorChain()
    	    			->attach(new FormValidator\StringLength(8));
    	    
    	    
    	    $this->add($email)
    	    				->add($password)
    	    					->setData($_POST);
    	    
    	    // As individual parameters
    	    $this->setValidationGroup('Username' , 'Pwd');
    	    
    	    return $this ;
    	}
}