<?php
namespace DV\View ;

use Zend\View\Helper\AbstractHelper as master_helper_abstract ;
use DV\Mvc\Service\ServiceLocatorFactory ;


class Base extends master_helper_abstract
{
	/**
	 * 
	 * @var \Zend\ServiceManager\ServiceManager
	 */
	protected $_sm ;
	
	
	public function __construct()
	{
		if(null == $this->_sm)	{
			$this->_sm = ServiceLocatorFactory::getInstance() ;
		}
	}
	
	protected function getLocator($service_name=null , $params=null)
	{
		return ServiceLocatorFactory::getLocator($service_name , $params)  ;
	}
}