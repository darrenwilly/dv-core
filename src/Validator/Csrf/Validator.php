<?php
namespace DV\Validator\Csrf ;

use Laminas\Validator\Csrf as CsrfValidator;
use Laminas\Math\Rand;

class Validator extends CsrfValidator
{
	/**
	public function __construct($options=array())
	{
		parent::__construct($options) ;
	}	
	
	 * Retrieve CSRF token
	 *
	 * If no CSRF token currently exists, or should be regenerated,
	 * generates one.
	 *
	 * @param  bool $regenerate    default false
	 * @return string
	 
	public function getHash($regenerate = false)
	{
		
		
		
		if ((null === $this->hash) || $regenerate) {
			$this->generateHash();
		}
		return $this->hash;
	}
	*/
	
	/**
	 * Generate CSRF token
	 *
	 * Generates CSRF token and stores both in {@link $hash} and element
	 * value.
	 *
	 * @return void
	 */
	protected function generateHash()
	{
		### generate token
		$token = md5($this->getSalt() . Rand::getBytes(32) .  $this->getName());
		
		### check for the validity of the session value first
		$csrf_container = $this->getSession() ;
		#$csrf_container = new \Laminas\Session\Container($this->getSessionName());;
		$csrf_container->setExpirationHops(5);
		#$csrf_container->setExpirationSeconds($this->getTimeout()) ;
		### check for existing csrf value in the tokenList & hash container && $csrf_container->__isset('hash')
		if(null != $_SESSION['Laminas_Validator_Csrf_salt_csrf']['tokenList'])	{
			### if the container value is still valid, then don't generate a new one
			$this->hash = $csrf_container->hash ;
			
			$this->setValue($this->hash);
		}
		else{
			
			### create the hash
			$hash = $this->formatHash($token, $this->generateTokenId()) ;
			
			### assign the hash value to the hash property
			$this->hash = $hash ;
		
			$this->setValue($this->hash);
			$this->initCsrfToken();	 			
		}	

	}
}