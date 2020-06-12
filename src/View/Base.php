<?php
namespace DV\View ;

use DV\ContainerService\ServiceLocatorFactory ;
use Psr\Container\ContainerInterface;
use Veiw\Infrastructure\Twig\TrojanViewHelperAbstract;

class Base extends TrojanViewHelperAbstract
{
	/**
	 * 
	 * @var ContainerInterface
	 */
	protected $_sm ;
	
	
	public function __construct(ContainerInterface $container)
	{

		if(null == $this->_sm)	{
			$this->_sm = $container ;
		}
		## check again
		if(null == $this->_sm)	{
			$this->_sm = ServiceLocatorFactory::getInstance() ;
		}
	}
	
	protected function getLocator($service_name=null , $params=null)
	{
		return ServiceLocatorFactory::getLocator($service_name , $params)  ;
	}
}