<?php
namespace DV\ErrorHandler\Json;

use DV\Service\UniqueGen;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;
use DV\Http\ResponseHeaders;

class DispatchResponse
{
    use ResponseHeaders ;

    public function __invoke(MvcEvent $event)
    {
        return call_user_func([$this , 'attachDispatchErrorHandler'] , $event) ;
    }

    public static function attachDispatchErrorHandler(MvcEvent $event)
    {   ## check if event is error
        if (! $event->isError()) {
            return;
        }

        ## make debugging easier if we're using xdebug!
        ini_set('html_errors', 0);
        ## get message and exception (if present)
        $message = $event->getError();
        $exception = $event->getParam('exception');
        $response = $event->getResponse();

        ## Route & Controller issue definition
        switch ($message) {
            case Application::ERROR_CONTROLLER_CANNOT_DISPATCH :
                $type = 'The requested controller was unable to dispatch the request.';
                break;
            case Application::ERROR_CONTROLLER_NOT_FOUND :
                $type = 'The requested controller could not be mapped to an existing controller class.';
                break;
            case Application::ERROR_CONTROLLER_INVALID :
                $type = 'The requested controller was not dispatchable.';
                break;
            case Application::ERROR_ROUTER_NO_MATCH:
                $type = 'The requested URL could not be matched by routing.';
                break;
            default:
                $type = 'General undescribed error';
                break;
        }

        ## generate unique reference for this error
        $errorReference = UniqueGen::md5Generate();
        $extra = ['reference' => $errorReference,
                   'type'  => $type ,
                    'httpStatus' => $response->getStatusCode(),
                    'title' => $response->getReasonPhrase(),
        ] ;

        ## check if event has exception and populate extras array.
        if (! empty($exception)) {
            $extra['message'] = $exception->getMessage();
            $extra['file'] = $exception->getFile();
            $extra['line'] = $exception->getLine();
            $extra['trace'] = $exception->getTrace();
            $extra['traceAsString'] = $exception->getTraceAsString();

            // check if xdebug is enabled and message present in which case add it to the extra
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

            #$event->getResponse()->setStatusCode($exception->getCode());
        }

        #$render = new JsonRenderer() ;
        $model = new JsonModel($extra);

        $self = new self() ;
        ### prepare response data
        $self->jsonErrorHeaders($response) ;

        ###
        $event->setResult($model);
        $event->setViewModel($model);
        #$this->mvcResponse('dispatch', $event, $extra);
    }
}