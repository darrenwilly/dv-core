<?php

namespace DV\Validator ;

use Zend\Validator\AbstractValidator as Zend_Validate ;
use DV\Service\ActionControl ;
use DV\Model\BaseTrait ;

class UniqueOnlineStatus extends Zend_Validate
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
    

    public function __construct($_options=[])
    {
    	### check if the model is set
    	if(! array_key_exists('model', $_options))	{
    		$_options['model'] = new \DV\Model\User() ;
    	}
    	
    	parent::__construct($_options) ;
    	
        $this->setModel($_options['model']) ;
    }

   
    public function isValid($value, $context = null)
    {
        ## Fetching User with provided Username
        $user = $this->getModel()->getUser(['row' => ['username' => $value, 'activated' => ActionControl::YES]]);
        
        ##check for null result
        if (null == count($user)) 		{
        	 $this->error(self::USER_NOT_EXISTS);
        	 return false ;
        }

        ##check for employee profile
        if (null == $user->getProfile()->getHrProfile() && ! in_array($user->getRole()->getRole() , ['superadmin' , 'hr'])) 		{
        	 $this->error(self::USER_PROFILE_NOT_COMPLETE);
        	 return false ;
        }
        
        ###
        if($user->getAccessLevel() > ActionControl::ONE)	{
        	$this->error(self::USER_NOT_ACTIVATED) ;
        	return false ;
        }
       
        ##if user onlineStatus == N means User actually logout successfully.
        if(ActionControl::NO === $user->getOnlineStatus())	{
        	return true;
        }
        	 
        ###else, user has some problem the last time he was online.
        elseif(ActionControl::YES === $user->getOnlineStatus())	{
			       ## create lastlogin date object
				$lastLogin = $user->getLastLogin() ;
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
                    if($this->getUserAccessTimeSettings($user)  <= $minuteResult)	{
                        return true ;
                    }
                    else{
                        $this->setMessage(vsprintf('User "%s" has a login status on the system, please retry again at the Administrator Set Time Interval.' ,
                                        [$user->getUsername()]) , self::USER_EXISTS);
                        $this->error(self::USER_EXISTS);
                        return false ;
                    }
			        	       		
				}
        
        }

        $this->error(self::USER_NOT_EXISTS) ;   
        return false ;     
   	 }
   	 
   	 
   	 public function getUserAccessTimeSettings($user)
   	 {
   	 	$user_group_setting = $this->getModel()->getSystem()->getSystemSettings(['row' => [
   	 								  'groupOptions' => 'time_to_wait_for_relogin_on_fail_signout' ,
   	 									'activated' => ActionControl::YES
   	 	]]) ;
   	 	
   	 	## check for null response
   	 	if(count($user_group_setting) != null)	{
   	 		$user_to_wait = $user_group_setting->getGroupSettings() ;
   	 	 
   	 		return $user_to_wait ;
   	 	}
   	 	else	{
   	 	 
	   	 	## Fetching User with provided Username
	   	 	$user_group_setting = $this->_model->getSystem()->getSystemSettings(['row' => ['groupOptions' => 'time_to_wait_for_relogin_on_fail_signout' ,
	   	 										'activated' => ActionControl::YES
	   	 	]]) ;
	   	 	
	   	 	if(null == count($user_group_setting))	{
	   	 		throw new \Exception('could detect an alternate admin system settings') ;
	   	 	}
	   	 	
	   	 	### use  the system default time settings
	   	 	$user_to_wait = (int) $user_group_setting->getGroupSettings() ;
	   	 		 
	   	 	return $user_to_wait ;
   	 	}
   	 	 
   	 }

}
