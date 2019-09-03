<?php
namespace DV\Cache\Listener;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteMatch;
use Zend\Http\Response;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Cache\StorageFactory;
use Zend\Cache\Storage\StorageInterface;

class PageCache extends AbstractListenerAggregate
{
	### "homepage": "https://github.com/juriansluiman/SlmCache",
	
    protected $cache_prefix = 'dv_cache_';

    protected $match;
    protected $serviceLocator;
    protected $cacheService ;

    public function __construct(ServiceLocatorInterface $sm)
    {
        $this->serviceLocator = $sm;

        $config = $sm->get('Config');

        if (isset($config['dv_cache']['cache_prefix'])) {
            $this->cache_prefix = $config['dv_cache']['cache_prefix'];
        }
        
        ### fetch the filesytem cache
        $cacheService = $sm->get('DV\Cache\Listener\Cache') ;
        
        if(! $cacheService instanceof \Zend\Cache\Storage\Adapter\Filesystem)	{
        	throw new \RuntimeException('unable to fetch configured instance of FileSystem for PageCache Listener');
        }
        
        $this->cacheService = $cacheService ;
    }

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events , $priority=1)
    {
		$this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE , [$this, 'matchRoute'] , -1000);
		$this->listeners[] = $events->attach(MvcEvent::EVENT_FINISH , [$this, 'saveRoute'] , -10000);
    }

    public function matchRoute(MvcEvent $e)
    {
    	### calling match method will generate the cache key according to information provided in the config file
        $match = $this->match($e);
        
        if (null === $match) {
            return;
        }
		
        $key    = $this->cache_prefix. $match['route'];
        $config = $match['config'];
        $cache  = $this->cacheService ;
        
        ### fetch the response
        $response = $e->getResponse();
        $result = $cache->getItem($key , $success);
        if ($success) {
        	### check and make sure the no of string returned is atleast greaten than 500 else means the file is corrupt       	
        	if(500 >= strlen($result))		{
        		###
        		$cache->flush() ;
        		##
        		return $this->saveRoute($e) ;
        	}
        	
			### set the response content
        	$response->setContent($result);
        	### add DV info to the header to know that the page was cache
        	$response->getHeaders()->addHeaderLine('X-DV-Cache', 'Fetch: Hit; route=' . $match['route']);
        	## set url params that the page has been cached
        	$e->setParam('cached', true);
        	
	        ### verify response object
	        if ($response instanceof Response) { 
	        	### return the response which will halt the mvc operation and return response
	            return $response;
	        }        	
        	
        } else {
        	$response->getHeaders()->addHeaderLine('X-DV-Cache', 'Fetch: Miss; route=' . $match['route']);
        }               

    }

    public function saveRoute(MvcEvent $e)
    {
        ### At EVENT_ROUTE the route did not match
        if (null === $this->match) {
            return;
        }

        ## Page just fetched from cache, no need to store
        if (true === $e->getParam('cached')) {
            return;
        }

        $key    = $this->cache_prefix. $this->match['route'];
        $config = $this->match['config'];
        $cache  = $this->cacheService ;

        $response = $e->getResponse();
        $response->getHeaders()->addHeaderLine('X-DV-Cache', 'Storage: Success; route=' . $this->match['route']);
        ###clear the cache first before saving new onew
        $cache->flush(); 
        ###
        $cache->setItem($key, $response->getContent());
    }

    protected function match(MvcEvent $e)
    {
    	## fetch the route match
        $match = $e->getRouteMatch();
        ###
        if (! $match instanceof RouteMatch) {
            return;
        }

        ### fetch the router name
        $route_name  = $match->getMatchedRouteName();
        ## fetch the application config
        $config = $this->serviceLocator->get('Config');
        ## get the router configured under dv_cache route
        $routes = $config['dv_cache']['routes'];

        ### check if the cache route is available in the app router config itself
        if (! array_key_exists($route_name , $routes)) {
            return;
        }
        
        ### fetch the value configured under the router name with same name in the DV_cache name.
        $config = (array) $routes[$route_name];

        ### Match HTTP request method to configured methods
        if (array_key_exists('match_method', $config)) {
        	### fetc hthe match_method configured for the current route name
            $methods = (array) $config['match_method'];
            ### fetch Request Method
            $method  = $e->getRequest()->getMethod();

            if (! in_array($method, $methods)) {
                return;
            }
        }

        ### Match route request parameters to configured parameters
        if (array_key_exists('match_route_params', $config)) {
            $params = (array) $config['match_route_params'];

            foreach ($params as $name => $value) {
                ## There is a specific route parameter
                if (is_string($value) && $value !== $match->getParam($name)) {
                    return;
                }

                ### There are multiple values possible
                if (is_array($value) && !in_array($match->getParam($name), $value)) {
                    return;
                }
            }
        }

        $match  = ['route' => $route_name , 'config' => $config] ;
        $this->match = $match;

        return $match;
    }

}
