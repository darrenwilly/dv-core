<?php

namespace DV\Mvc\Controller ;

use DV\Mvc\Controller\ActionController as CAction ;
use DV\Service\ActionControl ;


class AuthenticatedActionController extends CAction
{
    #protected $eventIdentifier = 'DV\Mvc\Controller\AuthenticatedActionController' ;

	public function __construct()
	{
		parent::__construct() ;
		
 		## call the redirector mehtod.
		$this->_check_authentication() ;
	}


	/**
	 * Check if the User has been authenticated.
	 *
	 * @throws \Exception
	 * @internal param array $redirector
	 */
	protected function _check_authentication()
	{
		if(! $this->isGranted('IS_AUTHENTICATED_FULLY'))    {
		    ##
            return $this->redirectToRoute('login') ;
        }
	}

	protected function acl()
    {
        $args = func_get_args() ;
    }
}
