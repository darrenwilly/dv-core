<?php
namespace DV\InputFilter ;

use DV\Service\TraitOptions;
use Laminas\InputFilter\InputFilter ;
use DV\Doctrine\Doctrine as doctrine_query ;


class Base extends InputFilter
{
	use doctrine_query ;
	use TraitOptions ;
	
	/**
	 * Instance of ServiceManager
	 * @var \Laminas\ServiceManager\ServiceManager()
	 */
	protected $_sm ;
	
	
	public function __construct($_options)
	{
		$this->setOptions($_options) ;
	}
}