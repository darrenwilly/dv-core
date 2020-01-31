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
		$auth_service = self::getLocator('get_auth_service') ;
			
		if(! ($auth_service->hasIdentity()))	{
			### fetch the requesting URI first
			$url_link = $_SERVER['REQUEST_URI'] ;
			
			### ask the browser to delete the cookie
			#Zend_Session::expireSessionCookie();
			## unset($_SESSION['Zend_Auth']) ;			
    		$auth_service->clear();
			
			### remove the session file to complete empty the session
			\DV\Service\System::remove_session_file() ;
            
			### append the requested uri to the link			
			return $this->redirect()->toRoute('login' , [] , ['query' => [ActionControl::REDIRECT_TO => urlencode($url_link)]]);
		}
	}
}
