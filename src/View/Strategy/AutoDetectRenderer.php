<?php 
namespace DV\View\Strategy;

use DV\Http\ResponseHeaders;
use DV\Mvc\APICallValidator;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait; 
use Zend\Http\Response;
use Zend\Mvc\Console\View\Renderer;
use Zend\View\Renderer\FeedRenderer;
use Zend\View\Renderer\JsonRenderer;
use Zend\View\ViewEvent;

class AutoDetectRenderer implements ListenerAggregateInterface
{
    use ResponseHeaders ;
    use ListenerAggregateTrait;
    use APICallValidator ;

    const TROJAN_STRATEGY = 'TrojanStrategy' ;

    protected $feedRenderer;
    protected $jsonRenderer;
    protected $listeners = array();
    protected $phpRenderer;
    protected $cliRenderer;

    public function __construct( $phpRenderer , JsonRenderer $jsonRenderer, FeedRenderer $feedRenderer , Renderer $cliRenderer)
    {
        $this->phpRenderer = $phpRenderer;
        $this->jsonRenderer = $jsonRenderer;
        $this->feedRenderer = $feedRenderer ;
        $this->cliRenderer = $cliRenderer ;
    }
    
    public function attach(EventManagerInterface $events , $priority = 100)
    {
        if (null === $priority) {
            $this->listeners[] = $events->attach(ViewEvent::EVENT_RENDERER , [$this, 'selectRenderer']);
            $this->listeners[] = $events->attach(ViewEvent::EVENT_RENDERER , [$this, 'injectResponse']);
        } else {
            $this->listeners[] = $events->attach(ViewEvent::EVENT_RENDERER , [$this, 'selectRenderer'], $priority);
            $this->listeners[] = $events->attach(ViewEvent::EVENT_RESPONSE , [$this, 'injectResponse'], $priority);
        }

    }

    /**
     * @param \Zend\Mvc\MvcEvent $e The MvcEvent instance
     * @return \Zend\View\Renderer\RendererInterface
     */
    public function selectRenderer($e)
    {
        $request = $e->getRequest();
        $headers = $request->getHeaders();

        ### options for Command Line
        if (0 === strpos(php_sapi_name() , 'cli')) {
            return $this->cliRenderer ;
        }

        if($request instanceof \Zend\Console\Request)    {
            return $this->cliRenderer ;
        }

        ### option for CORS Request from JS & JS Framework
        if($this->isAPICall($request))    {
            return $this->jsonRenderer ;
        }

        ### alternatively, use the accept header
         $accept = $headers->get('accept');

        if (null != $accept) {
            foreach ($accept->getPrioritized() as $mediaType) {
                ###
                if (0 === strpos($mediaType->getTypeString(), 'application/json')) {
                    return $this->jsonRenderer;
                }
                if (0 === strpos($mediaType->getTypeString(), 'application/rss+xml')) {
                    $this->feedRenderer->setFeedType('rss');
                    return $this->feedRenderer;
                }
                if (0 === strpos($mediaType->getTypeString(), 'application/atom+xml')) {
                    $this->feedRenderer->setFeedType('atom');
                    return $this->feedRenderer;
                }
                ##application/xhtml+xml for html
            }
         }
        // Nothing matched; return PhpRenderer. Technically, we should probably
        // return an HTTP 415 Unsupported response.
        return $this->phpRenderer;
    }

    /**
    * @param \Zend\Mvc\MvcEvent $e The MvcEvent instance
    * @return void
    */
    public function injectResponse($e)
    {
         $renderer = $e->getRenderer();
         $response = $e->getResponse();
         $result = $e->getResult();
         $request = $e->getRequest() ;
        
        if($request instanceof \Zend\Console\Request)    {
            #$response->setContent($result);
            return ;
        }

         ## fetch the header
         $headers = $response->getHeaders();

         if ($renderer == $this->jsonRenderer || $request->isOptions()) {
            ## call the necessary response header\
            $this->jsonResponseHeader($response) ;
         }
         elseif ($renderer === $this->feedRenderer) {
            // Feed Renderer; set content-type header, and export the feed if
            // necessary
            $feedType = $this->feedRenderer->getFeedType();
            #$headers = $response->getHeaders();
            $mediatype = 'application/' . (('rss' == $feedType) ? 'rss' : 'atom').'+xml';
            $headers->addHeaderLine('Content-Type', $mediatype);

            // If the $result is a feed, export it
             if ($result instanceof $this->feedRenderer) {
                 $result = $result->export($feedType);
             }
         } 
         elseif ($renderer !== $this->phpRenderer) {
             // Not a renderer we support, therefore not our strategy. Return
             return;
         }

        ## Inject the content
        $response->setContent($result);
    }

}