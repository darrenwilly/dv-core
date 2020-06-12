<?php

namespace DV\Crypt ;

use Laminas\Crypt\Password\Bcrypt as Laminas_bcrypt ;
use DV\Crypt\CryptAbstract as DV_bcrypt_abstract ;
use DV\Service\ActionControl ;
use Exception as CException ;


class Bcrypt
{
	protected $_salt ;
	
	protected $_hash ;
	
	protected $_string ;
	
	protected $_bcrypt ;
	
	
	
	public function __construct($_options=array())
	{
		$bcrypt =  new Laminas_bcrypt() ;
		
		if(array_key_exists('salt', $_options))	{
			$salt = $_options['salt'] ;
			$cost = ActionControl::THIRTEEN ;
			
			### check for cost key
			if(array_key_exists('cost', $_options))	{
				### overwrite the cost
				$cost = $_options['cost'] ;
			}					
		}
		else{
			$salt = parent::getSalt() ;
			$cost = ActionControl::THIRTEEN ;
		}
		
		$bcrypt->setSalt($salt) ;
		$bcrypt->setCost($cost) ;		
			
		### set the bcrypt object instance
		$this->setBCrypt($bcrypt) ;
		
		### return the object representation of bcrypt
		#return $this ;
	}
	
	public function create($string=null)
	{	
		if(null != $string)	{
			### set the string here
			$this->setString($string) ;
		}
		else{
			if(null == $this->getString())	{
				throw new CException('string to hash was not set') ;
			}
		}
		### create the hash string
		$create = $this->getBCrypt()->create($this->getString()) ;
		
		if(null == strlen($create))	{
			throw new CException('no string was hashed') ;
		}
		### set the hash as property
		$this->setHash($create) ;
		
		### return the string representation of hash
		return $create ;
	}
	
	
	public function verify($password_string=null, $hash=null)
	{
		if(null != $password_string)	{
			### set the string here
			$this->setString($password_string) ;
		}
		else{
			if(null == $this->getString())	{
				throw new CException('string to hash was not set') ;
			}
		}
		
		if(null != $hash)	{
			### set the string here
			$this->setHash($hash) ;
		}
		else{
			if(null == $this->getHash())	{
				throw new CException('hash password string was not set') ;
			}
		}
			
		return $this->getBCrypt()->verify($this->getString(), $this->getHash()) ;
	}

	/**
	 * fetch the string that was hashed in the hash property
	 * @return Laminas_bcrypt
	 * @internal param string $_hash
	 */
	public function getBCrypt()
	{
		return $this->_bcrypt ;
	}
	public function setBCrypt($bcrypt)
	{
		$this->_bcrypt = $bcrypt ;
	}

	/**
	 * fetch the string that was hashed in the hash property
	 *
	 * @return
	 * @internal param string $_hash
	 */
	public function getHash()
	{
		return $this->_hash ;
	}
	/**
	 * set the hash string value into a property
	 * 
	 * @param string $_hash
	 */
	public function setHash($_hash)
	{
		$this->_hash = $_hash ;
	}

	/**
	 * fetch the string representation of the value passed
	 *
	 * @return
	 * @internal param string $_string
	 */
	public function getString()
	{
		return $this->_string ;
	}
	/**
	 * set the string to be hashed
	 * 
	 * @param string $_string
	 */
	public function setString($_string)
	{
		$this->_string = $_string ;
	}
}