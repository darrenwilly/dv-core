<?php
namespace DV\Router\Listener;
 
use DV\Http\ResponseHeaders;
use DV\Mvc\LogicResult;
use DV\Mvc\Response\LogicResultResponse;
use DV\Mvc\Service\ServiceLocatorFactoryTrait;
use DV\Json\Validate;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\Http\Header\ContentType;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent; 

/**
 * Check and make sure that all necessary key to make request are made available
 *
 * Class HeaderRequirement
 * @package DV\Authentication\Api\Listener
 */
class ApachePatch implements ListenerAggregateInterface
{
    use ListenerAggregateTrait ;
    use Validate ;
    use ServiceLocatorFactoryTrait ;
    use ResponseHeaders ;

    /**
     * This Header Requirement Class is only enabled for Json expected output
     *
     * @param EventManagerInterface $event
     * @param int $priority
     */
    public function attach(EventManagerInterface $event , $priority=1)
    {
        $request = $this->getRequest() ;

        if(! $request instanceof Request)    {
            return ;
        }
        
        /*if($request->isOptions())    {
            return ;
        }*/

        $this->listeners[] = $event->attach(MvcEvent::EVENT_ROUTE , [$this , 'removePrependRewriteBase'] , 10000) ;
    }

    /**
     * Check for platform
     *
     * @param MvcEvent $e
     * @return \Zend\Stdlib\ResponseInterface
     */
    public function removePrependRewriteBase(MvcEvent $e)
    {
        ##
        $request = clone $e->getRequest() ;

        ## fetch the path of the url
        $uri = $request->getUri()->getPath() ;
        ##
        $prepended_url = '/'.BACKEND_ROUTE_ID ;

        if($request->isGet())    {
            /**
             * during development, some unnecessary error was happening and since we know that this is full frotend app, we decided to overide content type to application/json
             */
            $reqHeader = $request->getHeaders() ;
            ##
            if(! $reqHeader->has('content-type'))    {
                $reqHeader->addHeader(new ContentType('application/json')) ;
                ##
                $request->setHeaders($reqHeader);
                $e->setRequest($request) ;
            }
        }

        ## check if the /backend is part of the current url
        if(false === strpos($uri , $prepended_url))    {
            ##return ;
        }
        else{
            ##
            $rewrite_url = substr($uri , strlen($prepended_url) , strlen($uri)) ;

            ##
            $request->getUri()->setPath($rewrite_url) ;
        }

        ##
        $e->setRequest($request) ;
    }

   
}