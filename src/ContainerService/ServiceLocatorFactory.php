<?php

namespace DV\ContainerService ;

use DV\ContainerService\NullServiceLocatorException;
use Symfony\Component\HttpKernel\HttpKernel;


class ServiceLocatorFactory
{
    /**
     * @var \Zend\ServiceManager\ServiceManager
     */
    private static $serviceManager = null;
    
    /*
     * @var \Zend\Mvc\MvcEvent
     */
    private static $mvcEvent ;

    
    /**
     * @throw ServiceLocatorFactory\NullServiceLocatorException
     * @return \Zend\ServiceManager\ServiceManager
     */
    public static function getInstance()
    {
        if(null === self::$serviceManager) {
            throw new \Exception('ServiceLocator is not set');
        }

        return self::$serviceManager;
    }

    /**
     * @param ServiceManager
     */
    public static function setInstance($sm)
    {
        self::$serviceManager = $sm;
    }
        
    
    public static function getLocator($nameOrAlias = null, $params = null)
    {
    	if(null === self::getInstance()) {
    		throw new \Exception('ServiceLocator is not set');
    	}
    	
    	if ($nameOrAlias == null) {
    		return self::getInstance() ;
    	}
    
    	if (null == $params) {
    		return self::getInstance()->get($nameOrAlias);
    	}

    	return self::getInstance()->get('Di')->get($nameOrAlias, $params);
    }
        
    
    public static function setMvcEvent($mvcEvent)
    {
    	self::$mvcEvent = $mvcEvent ;
    }
    /**
     * lazy loading of mvcevent
     * @throws \Exception
     * @return MvcEvent
     */
    public static function getMvcEvent()
    {
    	if(null == self::$mvcEvent)	{
    		### fetch the application 
            $httpKernelEvent = self::getLocator('kernel') ;
    		### set the MVCEvent
    		self::setMvcEvent($httpKernelEvent) ;
    	}
    	
    	if(!self::$mvcEvent instanceof HttpKernel)	{
    		throw new \Exception('an instance of MVCEvent is required. instance of'. gettype(self::$mvcEvent).' passed') ;
    	}
    	
    	return self::$mvcEvent ;
    }
    
    /**
     * lazy load Request
     * @return \Zend\Stdlib\RequestInterface
     */
    public static function getRequest()
    {
    	return self::getInstance()->get('request_stack') ;
    }
    
    /**
     * lazy load Response
     * @return \Zend\Stdlib\ResponseInterface 
     */
    public static function getResponse()
    {
    	return self::getMvcEvent()->getResponse() ;
    }
    
    /**
     * lazy load Router
     * @return \Zend\Router\RouteStackInterface
     */
    public static function getRouter()
    {
    	return self::getMvcEvent()->getRouter() ;
    }
    
    /**
     * Lazy load RouteMatch
     * @return \Zend\Router\RouteMatch
     */
    public static function getRouteMatch()
    {
    	return self::getMvcEvent()->getRouteMatch() ;
    }

    /**
     * lazy load Request Parameters
     * @return \Zend\Stdlib\Parameters
     */
    public static function getParameters(array $defaults = array())
    {
        $request = self::getRequest(); /* @var $request \Zend\Http\PhpEnvironment\Request */
        if ($request->isGet()) {
            $parameters = $request->getQuery();
        } else {
            $parameters = $request->getPost();
        }

        $parameters->fromArray(array_merge($defaults, $parameters->toArray()));
        return $parameters;
    }

    /**
     * lazy load Request Parameters
     * @return \Zend\Stdlib\Parameters
     */
    public static function assembleUrl($route , $options=[]  , $query_params=[])
    {
        ### assign the router name
        $route_options = ['name' => $route] ;

        if(null != count($query_params))	{
            $route_options['query'] = (array) $query_params ;
        }

        ### fetch the mvc event
        $event  = self::getMVCEvent() ;
        ## assemble a uri
        $url = self::getRouter()->assemble($options , $route_options);

        return $url ;
    }
}