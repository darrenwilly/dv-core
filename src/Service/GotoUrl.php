<?php
namespace DV\Service ;

use DV\ContainerService\ServiceLocatorFactory ;
use Symfony\Component\HttpFoundation\RedirectResponse;


trait GotoUrl
{
	protected $router ;

	protected function _router(array $options)
	{				
		### check for router
		if(! isset($options['route']))    {
			$route = 'general/default' ;
		}
		else{
			$route = $options['route'] ; 
		}
		
		### check for url params
		if(! isset($options['route_params']))    {
			$route_params = [] ;
		}else{
			$route_params = $options['route_params'] ;
		}
		
		### check for url params
		if(! isset($options['query']))    {
			$query = null ;
		}else{
			$query = $options['query'] ;
		}
		
		$url = $this->getRouter()->generate($route) ;
		
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
		return new RedirectResponse($url , $statusCode) ;
	}

	public function setRouter($router)
    {
        $this->router = $router ;
    }
	public function getRouter()
    {
        return $this->router ;
    }
}