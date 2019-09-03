<?php
namespace DV\Crypt\Filter;

use DV\Filter\Base as dv_filter_base ;
use DV\Crypt\Data\Encryption as veiw_encryption ;
use DV\Service\ActionControl ;


abstract class Base extends dv_filter_base
{
	
	protected $dataLockKey ;
	
	protected $forceStrongEncryption ;
	
	/**
	 * Hold the instance of current location RSA Credentials
	 * @var \DV\Entity\TblWwwuserCurrentCommandLocation
	 */
	protected $currentCommand ;


	public function __construct($_options=[])
	{
		parent::__construct($_options) ;
	}	

	
	public function getDataLockKey()
	{
		return $this->dataLockKey ;
	}
	public function setDataLockKey($dataLockKey)
	{
		return $this->dataLockKey = $dataLockKey ;
	}
	
	/**
	 * create an instance of the encryption library
	 * @return \DV\Crypt\Data\Encryption
	 */
	public function getEncryptionLibrary()
	{
		$library = new veiw_encryption() ;
		return $library ;
	}	
	
	public function getForceStrongEncryption()
	{
		return $this->forceStrongEncryption ;
	}
	public function setForceStrongEncryption($forceStrongEncryption=ActionControl::YES)
	{
		return $this->forceStrongEncryption = $forceStrongEncryption ;
	}
	
	public function getCurrentCommand()
	{
		return $this->currentCommand ; 
	}
	
	public function setCurrentCommand(\DV\Entity\TblWwwuserCurrentCommandLocation $currentCommand)
	{
		return $this->currentCommand = $currentCommand ; 
	}
}