<?php
namespace DV\ErrorHandler;

use DV\Mvc\APICallValidator;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\Mvc\MvcEvent;
use Zend\View\Renderer\JsonRenderer;
use Zend\View\ViewEvent;
use DV\ErrorHandler\Json\{DispatchResponse , RenderResponse };

class ErrorHandler implements ListenerAggregateInterface
{
    use ListenerAggregateTrait ;
    use APICallValidator ;

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        /**
         * if a request is full Ajax and an error occurred, force usage of JSON RENDER
         */
        if ($this->isAPICall()) {
            ### when the request is from a JSON requesting App
            $sharedEventManager = $events->getSharedManager() ;
            ###
            $this->listeners[] = $sharedEventManager->attach(ViewEvent::class , MvcEvent::EVENT_RENDER_ERROR ,  function($e)   {
                return new JsonRenderer() ;
            }, 100);
            ##
            $this->listeners[] = $sharedEventManager->attach(\Zend\Mvc\Application::class , MvcEvent::EVENT_DISPATCH_ERROR , [DispatchResponse::class, 'attachDispatchErrorHandler'] ) ;
            ##
            $this->listeners[] = $sharedEventManager->attach(\Zend\Mvc\Application::class , MvcEvent::EVENT_RENDER_ERROR , [RenderResponse::class , 'attachRenderErrorHandler']) ;
        }

    }

}