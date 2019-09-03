<?php
namespace DV\Crypt\Filter;

use DV\Service\ActionControl;

class Decrypt extends Base
{
	
	public function filter($value=null)
	{		
		### $value must not be null at this point
		if(null == $value)	{
			return false;
		}	
		
		$message_body = $value ; 
		
		### increase the script execution time to 2min
		set_time_limit(120) ;
		
		### initiate the encryption library
		$encryption_lib = $this->getEncryptionLibrary() ;
		
		/**
		 * set the message lock key, so that it can be fetch from outside the class
		 */
		$this->setDataLockKey($this->getDataLockKey()) ;
		
		### check if the message has been flagged to use strong encryption
		if($this->getForceStrongEncryption() == ActionControl::YES)	{
			/**
			* use the current command public key to re-encryption the message
			*/
			$message_lock_key = $encryption_lib->rsa_operation(['data' =>  $this->getDataLockKey(),
										'todo' => 'decrypt',
										'private_key' => $this->getCurrentCommand()->getPrivateEncryptionKey() ,
										'pass_phrase' => $this->getCurrentCommand()->getEncryptionPassphrase()
			]) ;
			
		 	if(null != $this->getModel())	{
			 	$this->getModel()->add_flash_message('info' , 'your data was decrpted successfully') ;
			 }
			 
			 /**
			  * reset the message lock key, so that it can be fetch from outside the class
			  */
			 $this->setDataLockKey($message_lock_key) ;
		}
		
		### apply the first ordinary encryption
		$message_body = $encryption_lib->block_cipher_operation(['pass_phrase_key' => $this->getDataLockKey(),
										'todo' => 'decrypt',
										'data' => $message_body
		]) ;
	
		return $message_body ; 
	}
}