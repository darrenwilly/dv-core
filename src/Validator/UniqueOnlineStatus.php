<?php
declare(strict_types=1);
namespace DV\Validator ;

use Shared\Core\Query\User;
use Symfony\Component\Validator\ConstraintValidator as Validate ;
use DV\Service\ActionControl ;
use DV\MicroService\BaseTrait ;
use Symfony\Component\Validator\Constraint;

class UniqueOnlineStatus extends Validate
{
    use BaseTrait;

    const USER_EXISTS = 'userOnline';
    const USER_NOT_EXISTS = 'notExistHere';
    const USER_EXISTS_ON_DIFFRENT_BROWSER = 'userOnOtherBrowser';
    const USER_ONLINE_STATUS_YES = 'Y' ;
    const USER_ONLINE_STATUS_NO = 'N' ;
    const USER_NOT_ACTIVATED = 'userNotActivated' ;
    const USER_PROFILE_NOT_COMPLETE = 'userProfileNotComplete' ;
    /**
     * Compare value to use if the user is still on the same browser & IP i.e
     * User must have waited for more than 20minutes
     */
    const USER_COMPARED_MINUTE = 20 ;
    /**
     * Compare value to use if the user is still on another same browser & different IP i.e
     * User must have waited for more than 60minutes(1 Hour)
     */
    const USER_COMPARED_MINUTE_INHOUR = 60 ;
    /**
     * To Determine the lowest expected hour (01).
     * @var int
     */
    const EXPECT_HOUR_CLAUSE = 01 ;
    /**
     * Hours in Minute i.e 60min makes 1Hour
     * @var int
     */
    const HOUR_IN_MINUTE = 60 ; 
    
    
    protected $messageTemplates = [
       self::USER_EXISTS => '',
        self::USER_EXISTS_ON_DIFFRENT_BROWSER => 'User "%value%" has already sign-in on another system, Please retry in 60minutes.',
        self::USER_NOT_EXISTS => 'We don\'t know you, please contact administrator.',
    	self::USER_NOT_ACTIVATED => 'Your account %value% has not been activated / has been locked. Please contact administrator',
        self::USER_PROFILE_NOT_COMPLETE => 'The account %value% has not been approved by the HR Manager'
   ];

    protected $user_entity_row ;
    /**
     * allow us to control the behaviour of the validator when not used by Symfony Validator builder
     * @var int
     */
    protected $symfony_validator_behaviour = 1;

    public function __construct(User $model , $options=[])
    {
        $this->setModel($model) ;
    }
    
    public function validate($value, Constraint $constraint)
    {
        /**
         * Use the passed params to fetch only when the USer entity row is not set
         */
        if(! $user_entity_row = $this->getUserEntityRow())    {
            ##
            $user_entity_row = $this->getModel()->getUser(['row' => ['username' => $value, 'activated' => ActionControl::YES]]);
        }

        ##check for null result
        if (null == $user_entity_row) 		{
            $this->context->buildViolation($this->messageTemplates[self::USER_NOT_EXISTS])->addViolation();
        	 return false ;
        }

        ###
        if($user_entity_row->getAccessLevel() > ActionControl::ONE)	{
            if($this->symfony_validator_behaviour)    {
                $this->context->buildViolation($this->messageTemplates[self::USER_NOT_ACTIVATED])->addViolation();
            }

        	return false ;
        }
       
        ##if user onlineStatus == N means User actually logout successfully.
        if(ActionControl::NO === $user_entity_row->getOnlineStatus())	{
        	return true;
        }
        	 
        ###else, user has some problem the last time he was online.
        elseif(ActionControl::YES === $user_entity_row->getOnlineStatus())	{
			       ## create lastlogin date object
				$lastLogin = $user_entity_row->getLastLogin() ;
				## checking if d date is today or earlier than today.
					
				### create today's date
				$today = new \DateTime('today');
				### create now time
				$now = new \DateTime('now') ;					
					
				if(($lastLogin == $today) || ($lastLogin < $now))	{
                    ### substract the UserLastLogin Time from Current Time
                    $timeResult = $now->diff($lastLogin) ;
			        					        		
                    //check if the return Hour is more than One
                    if(self::EXPECT_HOUR_CLAUSE <= $timeResult->h)	{
                        ### convert the whole time(Hours & Minute)to Minute .
                        $minuteResult = (int) ($timeResult->h * self::HOUR_IN_MINUTE) + $timeResult->i ;
                    }
                    else{### use the existing minute
                        $minuteResult = (int) $timeResult->i ;
                    }
			        		
                    ### check if the user has waited for more-than / equal-to 20 minutes
                    if($this->getUserAccessTimeSettings($user_entity_row)  <= $minuteResult)	{
                        return true ;
                    }
                    else{
                        $message = sprintf('User "%s" has a login status on the system, please retry again at the Administrator Set Time Interval.' , $user_entity_row->getUsername());
                        if($this->symfony_validator_behaviour)    {
                            $this->context->buildViolation($message)->addViolation();
                        }

                        return false;
                    }
			        	       		
				}
        }

        if($this->symfony_validator_behaviour)    {
            $this->context->buildViolation($this->messageTemplates[self::USER_NOT_EXISTS])->addViolation() ;
        }

        return false ;
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

   	 
   	 public function getUserAccessTimeSettings($user_entity_row)
   	 {
   	 	$user_entity_row_group_setting = $this->getModel()->getSystem()->getSystemSettings(['row' => [
                    'groupOptions' => 'time_to_wait_for_relogin_on_fail_signout' ,
                    'activated' => ActionControl::YES
   	 	]]) ;
   	 	
   	 	## check for null response
   	 	if(($user_entity_row_group_setting) != null)	{
   	 		$user_entity_row_to_wait = $user_entity_row_group_setting->getGroupSettings() ;
   	 	    ##
   	 		return $user_entity_row_to_wait ;
   	 	}

   	 	throw new \Exception('could detect an alternate admin system settings') ;
   	 }

}
