<?php
namespace DV\Validator ;

use DV\Model\BaseTrait;
use DV\Model\Lookup;

class UniqueEmail extends Base
{ 
    use BaseTrait ;
    
	const NO_ADMIN_EMAIL = 'noAdminEmail' ;
	const EMAIL_EXISTS = 'emailExists' ;

    protected $model ;
    protected $repository ;
    protected $allowAdmin ;
    protected $field ;

    protected $messageTemplates = array(
        self::EMAIL_EXISTS => 'Email "%value%" already exists in our system',
        self::NO_ADMIN_EMAIL => 'Unable to fetch system administrative email',
    );

    public function __construct($options)
    {
        if(isset($options['model']))    {
            $this->setModel($options['model']) ;
            unset($options['model']) ;
        } 
        
        if(isset($options['repository']))    {
            $this->setRepository($options['repository']) ;
            unset($options['repository']) ;
        }
        
        if(isset($options['allowAdmin']))    {
            $this->setAllowAdmin($options['allowAdmin']) ;
            unset($options['allowAdmin']) ;
        }

        if(isset($options['field']))    {
            $this->setField($options['field']) ;
            unset($options['field']) ;
        }

        parent::__construct($options);
    }

    public function isValid($value, $context = null)
    {    	
        $this->setValue($value); 


        if($this->getAllowAdmin())    {
            ##
            $systemConfig = $this->_sm->get('config');
            ##
            if(! isset($systemConfig['darrismConfig']['miscEmail']))    {
                $this->error(self::NO_ADMIN_EMAIL) ;
                return false ;
            }
            ##
            if(in_array($value , $systemConfig['darrismConfig']['miscEmail'] , false))    {
                ##
                return true ;
            }
        }

        $model = new Lookup() ;

        $item = $model->getLookup(['row' => [$this->getField() => $value] , 'repository' => $this->getRepository()]) ;

        ##
        if($item == null)    {
            return true ;
        }

        $this->error(self::EMAIL_EXISTS);
        
        return false ;
    }
    
    public function setRepository($repository)
    {
        $this->repository = $repository;
    }
    public function getRepository()
    {
        return $this->repository ;
    } 
    
    public function setAllowAdmin(bool $admin=false)
    {
        $this->allowAdmin = $admin;
    }
    public function getAllowAdmin()
    {
        return $this->allowAdmin ;
    }

    public function setField($field='email')
    {
        $this->field = $field;
    }
    public function getField()
    {
        return $this->field ;
    }
}