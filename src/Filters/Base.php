<?php
namespace DV\Filter;

use DV\MicroService\TraitContainer;
use Psr\Container\ContainerInterface;
use Zend\Filter\AbstractFilter ;


abstract class Base extends AbstractFilter
{
	use TraitContainer ;

	/**
	 * Instance of ServiceManager
	 * @var \Zend\ServiceManager\ServiceManager()
	 */
	protected $sm ;
	
	protected $dataLockKey ;
	
	protected $forceStrongEncryption ;
	
	/**
	 * Hold the instance of current location RSA Credentials
	 * @var \Shared\Domain\Entity\TblWwwuser
	 */
	protected $currentCommand ;


	public function __construct($options=[])
	{
		if(isset($options['container']) && $options['container'] instanceof ContainerInterface)    {
		    ##
            $this->setContainer($options['container']) ;
        }
	}	

	
}