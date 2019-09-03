<?php
namespace DV\Service ;

use DV\Mvc\Service\ServiceLocatorFactory ;

 
trait GotoUrl
{
	
	protected function _router(array $_options)
	{				
		### check for router
		if(! isset($_options['route']))    {
			$route = 'general/default' ;
		}
		else{
			$route = $_options['route'] ; 
		}
		
		### check for url params
		if(! isset($_options['route_params']))    {
			$route_params = [] ;
		}else{
			$route_params = $_options['route_params'] ;
		}
		
		### check for url params
		if(! isset($_options['query']))    {
			$query = null ;
		}else{
			$query = $_options['query'] ;
		}
		
		$url = $this->_assemble($route_params , $route , $query) ;
		
		return self::redirector($url);		
	}

	/**
	 * Allow public access to redirect with using the gotourl implementation
	 *
	 * @param string $url
	 * @param int|number $statusCode
	 */
	static public function redirector($url , $statusCode=302)
	{
		$event = self::getMVCEvent() ;
		
		### fetch a response object from the MVCEVent
		$response = $event->getResponse() ;
		### fetch headers and add the url location for redirect
		$response->getHeaders()->addHeaderLine('Location', $url) ;
		### set a http response status code
		$response->setStatusCode($statusCode) ;
		### send the http header response.
		$response->sendHeaders();
		### exit the response
		exit;
	}
	
	
	/**
	 * 
	 * @param array $route_params
	 * @param string $route
	 * @param array $query
	 * 
	 * @return string
	 */
	static public function assemble($route_params , $route , $query=null)
	{
		return self::_assemble($route_params, $route , $query) ;
	}
	
	
	protected function _assemble($route_params , $route , $query=null)
	{
		if(null == $route_params)	{
			return false ;
		}
		
		if(null == $route)	{
			$route = 'general/default' ;
		}
		### assign the router name
		$options = ['name' => $route] ;
		
		if(null != $query)	{
			$options['query'] = $query ;
		}
		
		### fetch the mvc event
		$event  = self::getMVCEvent() ;		
		## assemble a uri
		$url = $event->getRouter()->assemble($route_params , $options);
		
		return $url ;
	}
	
	
	public static function getMVCEvent()
	{
		### fetch the MVCEvent instance from thhe application object
		$event = ServiceLocatorFactory::getMvcEvent() ;
		
		return $event ;
	}
}