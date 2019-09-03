<?php

namespace DV\Crypt ;

/**
 * Abstract class implementation of mcrypt engine
 * 
 * @abstract DV_Crypt_Abstract
 * @copyright Darrism Solutions Ltd
 * @author Darren
 * @version 1.0.0
 *
 */
trait CryptAbstract
{
	protected $_mode ;
	
	protected $_securekey ;
	
	protected $_iv ;
	
	protected $_cipher ;
	
	protected static $BLOCKSIZE = 256 ;

	/**
	 * Add padding to the provided character.
	* 
	* @param string $string Characters to pad
	* @param int $blocksize default block size to add to string for advanced securities
	* @return string
	*/
	public function addpadding($string, $blocksize = 32)
	{
		 $len = strlen($string);
		 $pad = $blocksize - ($len % $blocksize);
		 $string .= str_repeat(chr($pad), $pad);
		 return $string;
	}
		 
	/**
	* Strip off the added padding to the provided character.
	*
	* @param string $string Characters to pad
	* @return string
	*/
	public function strippadding($string)
	{
		 $slast = ord(substr($string, -1));
		 $slastc = chr($slast);
		 $pcheck = substr($string , -$slast);
		 	
		 if(preg_match("/$slastc{".$slast."}/", $string))	{
		 	$string = substr($string, 0, strlen($string)-$slast);
		 	return $string;
		 } else {
		 	return false;
		 }
	}


	/**
	 * get the mcrypt IV with 128bit using mcrypt_create_iv in more sophisticated mode.
	 *
	 * @return
	 * @internal param string $iv
	 */
	public function getIv()
	{
		 return $this->_iv ;
	}		 
		 /**
		  * set the mcrypt IV with 128bit using mcrypt_create_iv in more sophisticated mode.
		  * 
		  * @param string $iv
		  */
	public function setIv($iv)
	{
		 $this->_iv = $iv ;
	}
		 
		 
	public function getCipher()
	{
		 return $this->_cipher ;
	}

	/**
	 *
	 * @param string $_cipher
	 * @return MYCRYPT_CIPHER_TYPE
	 * @internal param Cipher $the type to set $_cipher defaulted to MCRYPT_RIJNDAEL_256
	 */
	public function setCipher($_cipher = MCRYPT_RIJNDAEL_256)
	{
		 $this->_cipher = $_cipher ;
	}
		 
		 
		 /**
		  * get the already set mcrypt mode
		  * @return mycrypt mode
		  */
	public function getMode()
	{
		 return $this->_mode ;
	}

	/**
	 * set the mcrypt mode
	 * @param string $_mode
	 * @return mycrypt mode
	 */
	public function setMode($_mode = MCRYPT_MODE_CBC)
	{
		 $this->_mode = $_mode ;
	}

		 
		 /**
		  * get the Security key
		  */
	public function getSecurityKey()
	{
		 return $this->_securekey ;
	}

	/**
	 * set the mcrypt security key
	 *
	 * @param $_key
	 * @return Mcrypty security key
	 */
	public function setSecurityKey($_key)
	{
		 $this->_securekey = $_key ;
	}


	/**
	 * Generate a 128bit encrypted salt to be as Mcrypt IV
	 *
	 * @param int $block
	 * @param int $source
	 * @return string 128Bit Salted value.
	 */
	public static function getSalt($block = 256 , $source = false)
	{
		 $salt = strtr(base64_encode(openssl_random_pseudo_bytes($block , $source)), '+', '.');
		 return $salt ;
	}
		
}