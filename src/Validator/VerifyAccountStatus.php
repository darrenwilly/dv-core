<?php

namespace DV\Validator ;

use DV\MicroService\TraitModel;
use DV\Service\FlashMessenger;
use Shared\Core\Query\User;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator as Laminas_Validate ;


class VerifyAccountStatus extends Laminas_Validate
{ 
	use TraitModel ;

	const ACCOUNT_DISABLED = 'accountdisabled' ;
	const ACCOUNT_NON_EXIST = 'accountnotexist' ;
	const ACCOUNT_NOT_ACTIVATED = 'accountnotactivated' ;
	const ACCOUNT_NO_PORTAL_ACCESS = 'accountnoportalaccess' ;
	const UNKWOWN_RESULT = 'unkwownresult' ;

    
    protected $messageTemplates = array(
        self::ACCOUNT_DISABLED => 'Account "%value%" has been disabled,
        						kindly contact the Darrism Solutions Customer Support' ,
        self::ACCOUNT_NON_EXIST => 'Account "%value%" does not exist or deleted' ,
        self::ACCOUNT_NO_PORTAL_ACCESS => 'Account "%value%" is not enabled for portal access,
        						kindly contact the Darrism Solutions Customer Support' ,
        self::UNKWOWN_RESULT => 'Your account has failed due to unkwown problem' 
    );
    
    protected $user_entity_row;
    /**
     * allow us to control the behaviour of the validator when not used by Symfony Validator builder
     * @var int
     */
    protected $symfony_validator_behaviour = 1;

	public function __construct(User $model)
	{
		 $this->setModel($model) ;
	}

 	public function validate($value, Constraint $constraint)
    {
        ##
        if(! $user_entity_row = $this->getUserEntityRow())    {
            $user_entity_row = $this->getModel()->getUser(['row' => ['username' => $value]]) ;
        }
        
        ### check for null
        if (null == $user_entity_row) {
            if($this->symfony_validator_behaviour)    {
                $this->context->buildViolation($this->messageTemplates[self::ACCOUNT_NON_EXIST])->addViolation() ;
            }

            return false ;
        }   
        
        ### cast the accessLevel value as integer
        $accessLevel = (int) $user_entity_row->getAccessLevel() ;
        
        SWITCH(strtolower($user_entity_row->getUserType()))	{

        	### allow this check only on grantee account	
        	case 'admin' :
        			SWITCH(true)	{        				
        				case $accessLevel == 1 :
        						return true ;
        					BREAK ;
        					
        				case $accessLevel == 2 :
                            if($this->symfony_validator_behaviour) {
                                $this->context->buildViolation($this->messageTemplates[self::ACCOUNT_NO_PORTAL_ACCESS])
                                    ->setParameters(['%value%' => $user_entity_row->getUsername()])
                                    ->addViolation();
                            }
        						return false ;
        					BREAK ;
        					
        				case $accessLevel == 3 : # suspended
                            if($this->symfony_validator_behaviour) {
                                $this->context->buildViolation($this->messageTemplates[self::ACCOUNT_DISABLED])
                                    ->setParameters(['%value%' => $user_entity_row->getUsername()])
                                    ->addViolation();
                            }
        						return false ;
        					BREAK ;
        					
        				default :
                            if($this->symfony_validator_behaviour) {
                                $this->context->buildViolation($this->messageTemplates[self::UNKWOWN_RESULT])
                                    ->setParameters(['%value%' => $user_entity_row->getUsername()])
                                    ->addViolation();
                            }
        					return false ;
        					BREAK ;
        			}			        		        
			        
        		break ;
        		
        	default :
        			throw new \RuntimeException($this->messageTemplates[self::UNKWOWN_RESULT]) ;
        		break;
        }

    }

    public function setUserEntityRow($user_entity_row)
    {
        $this->user_entity_row = $user_entity_row ;
    }
    public function getUserEntityRow()
    {
        return $this->user_entity_row ;
    }
    public function setBehaviour($behaviour)
    {
        $this->symfony_validator_behaviour = $behaviour ;
    }

}