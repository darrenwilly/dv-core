<?php

namespace DV\Mvc\Service ;

use Zend\Mvc\MvcEvent ;

trait ServiceLocatorFactoryTrait
{
    /**
     * @throw ServiceLocatorFactory\NullServiceLocatorException
     * @return \Zend\ServiceManager\ServiceManager
     */
    public function getServiceManager()
    {
        return ServiceLocatorFactory::getInstance();
    }
    
    public function getLocator($nameOrAlias = null, $params = null)
    {
    	return ServiceLocatorFactory::getLocator($nameOrAlias , $params) ;
    }
        

    /**
     * lazy loading of mvcevent
     * @throws \Exception
     * @return MvcEvent
     */
    public function getMvcEvent()
    {
    	return ServiceLocatorFactory::getMvcEvent();
    }
    
    /**
     * lazy load Request
     * @return \Zend\Stdlib\RequestInterface
     */
    public function getRequest()
    {
    	return ServiceLocatorFactory::getRequest() ;
    }
    
    /**
     * lazy load Response
     * @return \Zend\Stdlib\ResponseInterface 
     */
    public function getResponse()
    {
    	return ServiceLocatorFactory::getResponse() ;
    }
    
    /**
     * lazy load Router
     * @return \Zend\Router\RouteStackInterface
     */
    public function getRouter()
    {
    	return ServiceLocatorFactory::getRouter() ;
    }
    
    /**
     * Lazy load RouteMatch
     * @return \Zend\Router\RouteMatch
     */
    public function getRouteMatch()
    {
    	return ServiceLocatorFactory::getRouteMatch();
    }

    /**
     * lazy load Request Parameters
     * @return \Zend\Stdlib\Parameters
     */
    protected function getParameters(array $defaults = [])
    {
        return ServiceLocatorFactory::getParameters($defaults) ;
    }

    /**
     * lazy load Request Parameters
     * @return \Zend\Stdlib\Parameters
     */
    protected function assembleUrl($route , $options=[]  , $query_params=[])
    {
        return ServiceLocatorFactory::assembleUrl($route , $options , $query_params) ;
    }

    public function getViewModel()
    {
        return $this->getMvcEvent()->getViewModel() ;
    }

    public function isAjax()
    {
        $request = $this->getRequest() ;

        if(! $request->isXmlHttpRequest())    {
            return false ;
        }

        $response = $this->getResponse() ;
        $headers = $response->getHeaders();
        $headers->addHeaderLine('content-type', 'application/json');

        return true ;
    }
}