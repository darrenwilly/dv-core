<?php
namespace DV\Crypt\Filter;

use DV\Service\ActionControl ;
use DV\Service\UniqueGen ;

class Encrypt extends Base
{
	protected $_adapter ;
	
	protected $_current_command ;
	
	protected $_message_entity ;
	
	protected $_model ;
	
	protected $_force_strong_encryption ;
	
	protected $_message_lock_key ;
	
	
	public function filter($value=null)
	{	
		### $value must not be null at this point
		if(null == $value)	{
			return false; 
		}
		
		### increase the script execution time to 2min
		set_time_limit(120) ;
		
		### initiate the encryption library
		$encryption_lib = $this->getEncryptionLibrary() ;
		
		### fetch the message lock key
		$message_lock_key = $this->getDataLockKey() ;
		
		### check incase a message lock key is empty
		if(null == strlen($message_lock_key))	{
			$message_lock_key = $encryption_lib->pass_phrase(['phrase' => UniqueGen::printString(PROJECT_NAME , 16)]) ;
		}		

		/**
		 * set the message lock key, so that it can be fetch from outside the class
		 */
		$this->setDataLockKey($message_lock_key) ;
		
		/**
		 * The mode of message encryption now, is to rsa encryption a key or use the key as it is.
		 * then the online cloud server will use the sender private key to interprete the message_lock_key to open the message,
		 * then the cloud will use the receiver public key to re-encrypt the message_lock_key, so that the receiver can use
		 * the private key to interprete the message_lock_key before using it to open the message
		 */
		$message_body = $encryption_lib->block_cipher_operation(['pass_phrase_key' => $this->getDataLockKey(),
				'todo' => 'encrypt',
				'data' => $value
		]) ;
		
		/**
		 * check if the message is strong encryption, then encrypt the key so that it cannot be used
		 * to unlock the message until the key has been decrypted
		 */
		if($this->getForceStrongEncryption() == ActionControl::YES)	{
			
			 $message_lock_key = $encryption_lib->rsa_operation(['data' => $this->getDataLockKey() ,
											 'todo' => 'encrypt',
											 'public_key' => $this->getCurrentCommand()->getPublicEncryptionKey()
			 ]) ;
			 
			 if(null != $this->getModel())	{
			 	$this->getModel()->add_flash_message('info' , 'your data encryption was successful') ;
			 }			 

			 /**
			  * re-set the message lock key, so that it can be fetch from outside the class
			  */
			 $this->setDataLockKey($this->getDataLockKey()) ;
		}
		
			
		return $message_body ;
	}

}