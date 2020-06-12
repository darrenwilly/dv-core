<?php
namespace DV\ErrorHandler\Json;

use DV\Http\ResponseHeaders;
use DV\Service\UniqueGen;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;
use Laminas\View\Renderer\JsonRenderer;

class RenderResponse
{
    use ResponseHeaders ;

    public function __invoke(MvcEvent $event)
    {
        return call_user_func([$this , 'attachRenderErrorHandler'] , $event) ;
    }

    public static function attachRenderErrorHandler(MvcEvent $event)
    {
        ### check if event is an error
        if (! $event->isError()) {
            return;
        }

        // get message and exception (if present)
        $message = $event->getError();
        $exception = $event->getParam('exception');

        $type = 'RENDER';

        // exception filter
        if (! empty($exception)) {
            return;
        }

        // generate unique reference for this error
        $errorReference = UniqueGen::md5Generate();
        $extra = array(
            'reference' => $errorReference,
            'type'  => $type
        );

        ## check if event has exception and populate extras array.
        if (! empty($exception)) {
            $extra['message'] = $exception->getMessage();
            $extra['file'] = $exception->getFile();
            $extra['line'] = $exception->getLine();
            $extra['trace'] = $exception->getTrace();
            $extra['traceAsString'] = $exception->getTraceAsString();

            if (isset($exception->xdebug_message)) {
                $extra['xdebug'] = $exception->xdebug_message;
            }

            if (method_exists($exception, 'getSeverity')) {
                $extra['errorType'] = $exception->getSeverity();
            }

            // find the previous exceptions
            $messages = array();
            while ($exception = $exception->getPrevious()) {
                $messages[] = "* " . $exception->getMessage();
            };
            if (count($messages)) {
                $exceptionString = implode("n", $messages);
                $extra['previous'] = $exceptionString;
            }
        }

        $renderer = new JsonRenderer() ;
        $model = new JsonModel($extra);
        $response = $event->getResponse() ;

        ### prepare response data
        self::jsonErrorHeaders($response) ;
        $event->getResponse()->setStatusCode($exception->getCode());

        ###
        $response->setContent($renderer->render($model)) ;

        return $response ;
    }
}