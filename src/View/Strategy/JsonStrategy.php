<?php 
namespace DV\View\Strategy;

use DV\Http\ResponseHeaders;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateTrait;
use Laminas\Http\Response;
use Laminas\View\Renderer\JsonRenderer;
use Laminas\View\Strategy\JsonStrategy as zfStrategy;
use Laminas\View\ViewEvent;
use Laminas\View\Model\JsonModel ;

class JsonStrategy extends zfStrategy
{
    use ResponseHeaders ;
    use ListenerAggregateTrait;

    const TROJAN_STRATEGY = 'TrojanStrategy' ;

    protected $feedRenderer;
    protected $jsonRenderer;
    protected $listeners = array();
    protected $phpRenderer;
    protected $cliRenderer;


    /**
     *  register at high priority, to "beat" normal json strategy registered
     *  via view manager, as well as HAL strategy.
     */
    public function attach(EventManagerInterface $events, $priority = 400)
    {
        $this->listeners[] = $events->attach(ViewEvent::EVENT_RENDERER, [$this, 'selectRenderer'], $priority);
        $this->listeners[] = $events->attach(ViewEvent::EVENT_RESPONSE, [$this, 'injectResponse'], $priority);
    }

    /**
     * @param \Laminas\Mvc\MvcEvent $e The MvcEvent instance
     * @return \Laminas\View\Renderer\RendererInterface
     */
    public function selectRenderer(ViewEvent $e)
    {
        $model = $e->getModel();

        if (!$model instanceof JsonModel) {
            // no JsonModel; do nothing
            $this->renderer = new JsonRenderer();
        }

        // JsonModel found
        return $this->renderer;
    }

    /**
    * @param \Laminas\Mvc\MvcEvent $e The MvcEvent instance
    * @return void
    */
    public function injectResponse(ViewEvent $e)
    {
        $renderer = $e->getRenderer();
        if ($renderer !== $this->renderer) {
            // Discovered renderer is not ours; do nothing
            return;
        }

        $result   = $e->getResult();
        if (!is_string($result)) {
            // We don't have a string, and thus, no JSON
            return;
        }

        // Populate response
        $response = $e->getResponse();
        $response->setContent($result);
        $headers = $response->getHeaders();

        if ($this->renderer->hasJsonpCallback()) {
            $contentType = 'application/javascript';
        } else {
            $contentType = 'application/json';
        }

        if(! $headers->has('Content-Type')) {
            $contentType .= '; charset=' . $this->charset;
            $headers->addHeaderLine('content-type', $contentType);
        }

        ###
        $this->jsonResponseHeader($response) ;

        if (in_array(strtoupper($this->charset), $this->multibyteCharsets)) {
            $headers->addHeaderLine('content-transfer-encoding', 'BINARY');
        }
    }

}