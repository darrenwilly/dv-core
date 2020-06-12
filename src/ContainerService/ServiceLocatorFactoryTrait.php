<?php

namespace DV\ContainerService ;

use DV\ContainerService\NullServiceLocatorException;
use Laminas\Stdlib\Parameters;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

trait ServiceLocatorFactoryTrait
{
    /**
     * @throw ServiceLocatorFactory\NullServiceLocatorException
     * @return ContainerInterface
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
     * lazy load Request
     * @return \Symfony\Component\HttpFoundation\RequestStack
     */
    public function getRequest()
    {
        return $this->getLocator('request_stack') ;
    }
    
    /**
     * lazy load Response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getResponse()
    {
    	return new Response() ;
    }
    
    /**
     * lazy load Router
     * @return  \Symfony\Component\Routing\RouterInterface
     */
    public function getRouter()
    {
    	return $this->getLocator('router.default') ;
    }

    /**
     * lazy load Request Parameters
     * @return array | Parameters
     */
    protected function getParameters(array $defaults = [])
    {
        return ServiceLocatorFactory::getParameters($defaults) ;
    }

    /**
     * lazy load Request Parameters
     * @return string
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

        if($request instanceof RequestStack)    {
            ##
            $request = $request->getCurrentRequest() ;
        }

        if(! $request->isXmlHttpRequest())    {
            return false ;
        }
        ## fetch header object
        $response = $this->getResponse() ;
        ##
        $headers = $response->headers;
        ##
        $headers->set('content-type', 'application/json');
        ##
        return true ;
    }
}