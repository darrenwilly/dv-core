<?php
namespace DV\Cache\Listener ;

use Laminas\EventManager\AbstractListenerAggregate ;
use Laminas\EventManager\EventManagerInterface ;
use Laminas\Mvc\MvcEvent ;
use DV\Mvc\Service\ServiceLocatorFactory ;

class CacheListener extends AbstractListenerAggregate
{
	/** example
	 * http://www.ryrobbo.com/post/zf2-cache-3-full-page-caching
	 * @var unknown
	 */
	protected $listeners = [] ;
	protected $cacheService ;
	
	
	public function __construct($cacheService=null)
	{
		if(null == $cacheService || (! $cacheService instanceof \Laminas\Cache\Storage\Adapter\Filesystem))	{
			$cacheService = ServiceLocatorFactory::getLocator('DV\Cache\Listener\Cache') ;
		}	
		$this->cacheService = $cacheService ;
	}
	
	
	public function attach(EventManagerInterface $events)
	{
		### The AbstractListenerAggregate we are extending from allows us to attache our even listeners
		$this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE , [$this , 'getCache'] , -1000);
		$this->listeners[] = $events->attach(MvcEvent::EVENT_RENDER , [$this , 'saveCache'] , -10000);
	}
	
	
	public function getCache(MvcEvent $event)
	{
		### fetch the route match
		$match = $event->getRouteMatch(); 
		
		### is valid route?
		if(! $match)	{
			return ;
		}

		### does our route have the cache flag set to true
		if($match->getParam('cache'))	{
			###
			$cacheKey = $this->genCacheName($match);					
			## get the cache page for this route
			$data = $this->cacheService->getItem($cacheKey) ;
				
			### ensure we have found something valid
			if($data !== null)	{
				$response = $event->getResponse() ;
				$response->setContent($data) ;
				
				## will halt the execution of the page
				return $response ;
			}
		}		
		
	}
	
	
	public function saveCache(MvcEvent $event)
	{
		### fetch route match
		$match = $event->getRouteMatch() ;
		
		### is valid route?
		if(! $match)	{
			return ;
		}
		
		### does our route have the cache flag set to true
		if($match->getParam('cache'))	{
			###
			$response = $event->getResponse() ;
			##
			$data = $response->getContent() ;
			
			###
			$cacheKey = $this->genCacheName($match);
			##
			$this->cacheService->setItem($cacheKey , $data) ;
		}
	}
	
	
	public function genCacheName($match)
	{
		return 'cache_'. str_replace('/' , '-' , $match->getMatchRouteName() . '-' . md5(serialize($match->getParams()))) ;
	}
	
}