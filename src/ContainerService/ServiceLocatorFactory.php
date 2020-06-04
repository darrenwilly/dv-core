<?php

namespace DV\ContainerService ;

use DV\ContainerService\NullServiceLocatorException;
use Laminas\Stdlib\Parameters;
use Root\Kernel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernel;


class ServiceLocatorFactory
{
    /**
     * @var ContainerInterface
     */
    private static $serviceManager = null;

    /**
     * @var Kernel
     */
    private static $mvcEvent ;
    private static $request ;

    
    /**
     * @throw ServiceLocatorFactory\NullServiceLocatorException
     * @return ContainerInterface
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

    /**
     * @param null $nameOrAlias
     * @param null $params
     * @return object|ContainerInterface|null|callable
     * @throws \Exception
     */
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
    public static function setHttpKernel($kernel)
    {
    	self::$mvcEvent = $kernel ;
    }

    /**
     * lazy loading of mvcevent
     * @throws \Exception
     * @return Kernel
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
     * @return \Symfony\Component\HttpFoundation\RequestStack
     */
    public static function getRequest()
    {
        if(null == self::$request)    {
            self::$request = self::getInstance()->get('request_stack') ;
        }
        return self::$request ;
    }
    public static function setRequest($request)
    {
    	self::$request = $request ;
    }
    
    /**
     * lazy load Response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public static function getResponse()
    {
    	return new Response() ;
    }
    
    /**
     * lazy load Router
     * @return  Symfony\Component\Routing\RouterInterface
     */
    public static function getRouter()
    {
    	return self::getInstance()->get('router.default') ;
    }
    

    /**
     * lazy load Request Parameters
     * @return array | \Laminas\Stdlib\Parameters
     */
    public static function getParameters(array $defaults=[]  , $options=[] , $returnLaminasParameter=false)
    {
        /* @var $request \Symfony\Component\HttpFoundation\Request */
        $requestStack = self::getRequest();

        /**
         * You have to check for HttpRequest First before checking for Request Stack
         */
        if(! $requestStack instanceof Request)    {
            ##
            if(! $requestStack instanceof RequestStack)    {
                ##
                throw new \RuntimeException('Invalid Request Stack parameter') ;
            }
            else{
                ##
                $currentRequest = ($requestStack->getCurrentRequest());
            }
        }
        else{
            $currentRequest = $requestStack ;
        }

        ##
        $params = [] ;
        ##
        $query = $currentRequest->query ;
        $request = $currentRequest->request ;

        #if($query instanceof \Symfony\Component\HttpFoundation\ParameterBag )    {}

        $params = array_merge($params , $query->all() , $request->all() , $defaults) ;
        ## check if server params should be add
        if(isset($options['server']))    {
            $server = $currentRequest->server ;
            $params = array_merge($params , $server->all())  ;
        }

        ##
        if(isset($options['attr']) || isset($options['attribute'])) {
            $attribute = $currentRequest->attributes ;
            $params = array_merge($params , $attribute->all()) ;
        }

        ##
        if($returnLaminasParameter && class_exists(\Laminas\Stdlib\Parameters::class))    {
            ##
            return new Parameters($params) ;
        }
        ##
        return $params ;
    }

    /**
     * lazy load Request Parameters
     * @return string
     */
    public static function assembleUrl($route , $options=[]  , $query_params=[])
    {
        ### assign the router name
        $route_options = ['name' => $route] ;

        if(null != count($query_params))	{
            $route_options['query'] = (array) $query_params ;
        }
        ##
        $url = self::getLocator('router.default');

        return $url->generate($route , array_merge($options , $query_params) );
    }
}