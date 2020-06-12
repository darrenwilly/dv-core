<?php
namespace DV\Mvc ;

use Laminas\Stdlib\ArrayObject as LogicResultModel;
use Exception ;
use Throwable ;
use Symfony\Component\HttpFoundation\Response ;
use Laminas\InputFilter\InputFilter;

/**
 * Object describing an API-Response payload.
 */
class LogicResult
{
    /**
     * Content type for api problem response
     */
    const CONTENT_TYPE = 'application/json; charset=utf8';

    const CONTENT_TYPE_ERROR = 'application/problem+json';

    /**
     * Additional details to include in report.
     *
     * @var array
     */
    protected $additionalDetails = [];

    /**
     * Description of the specific problem.
     *
     * @var string
     */
    protected $message ;

    /**
     * ViewModel or Json Model
     *
     * @var LogicResultModel
     */
    protected $viewModel ;

    /**
     * HTTP status for the error.
     *
     * @var int
     */
    protected $status;

    protected $exception;

    protected $activateError = false;

    /**
     * Normalized property names for overloading.
     *
     * @var array
     */
    protected $normalizedProperties = [
        'type' => 'type',
        'status' => 'status',
        'message' => 'message'
    ];


    /**
     * Constructor.
     *
     *
     * @param array|JsonModel $options
     */
    public function __construct($options=null)
    {
        ### set the Datamodel on instantiation
        $this->setDataModel(new LogicResultModel($this)) ;

        if($options instanceof JsonModel || $options instanceof LogicResultModel)   {
            $this->processJsonModel($options) ;
        }
        elseif($options instanceof LogicResult)    {
            $this->merge($options) ;
        }
        elseif($options instanceof Exception)    {
            $this->processException($options) ;
        }
        elseif(is_string($options))     {
            $this->processString($options) ;
        }
        elseif(is_array($options)) {
            $this->processArray($options) ;
        }
        elseif($options instanceof InputFilter) {
            $this->processInputFilter($options) ;
        }
    }

    protected function processString($string)
    {
        $model = $this->getDataModel() ;
        ###
        $model->setMessage(['type' => 'success' , 'content' => (array) $string]) ;
        $model->setStatus(Response::STATUS_CODE_200) ;
        $model->setType(self::CONTENT_TYPE) ;
    }
    protected function processJsonModel(JsonModel $jsonModel)
    {
        $model = $this->getDataModel() ;
        ##
        if($jsonModel->__isset('error'))    {
            $model->setActivateError(true) ;
            $model->setType(self::CONTENT_TYPE_ERROR) ;
            $model->setStatus(Response::STATUS_CODE_500) ;
        }
        else{
            $model->setType(self::CONTENT_TYPE);
        }
        $model->addChild($jsonModel) ;
    }
    protected function processException(\Exception $exception)
    {
        $model = $this->getDataModel() ;
        $model->setActivateError(true) ;
        $model->setType(self::CONTENT_TYPE_ERROR);
        $model->setMessage(['type' => 'error' , 'content' => (array) $exception->getMessage()]) ;
        $model->setStatus($this->createStatusFromException($exception)) ;
        $model->setAdditionalDetails($this->createDetailFromException($exception));
        ###
        $model->setException($exception) ;
    }
    protected function processArray($options)
    {
        $model = $this->getDataModel() ;
        $model->setType(self::CONTENT_TYPE) ;
        ###
        if(isset($options['model'])) {
            $this->processJsonModel($options['model']) ;
        }

        ### condition that check for error message to know the appropriate headers
        if (isset($options['error'])) {
            $model->setActivateError(true) ;
            ### set a default status code for error message
            if(! isset($options['status']))    {
                $model->setStatus(Response::STATUS_CODE_500) ;
            }
            $model->setType(self::CONTENT_TYPE_ERROR);
        }

        ## condition that check for message that will be display
        if (isset($options['message'])) {
            ##
            if(is_string($options['message']))    {
                 $message = $options['message'] ;
                 unset($options['message']) ;
                 ##
                $type = (true === $model->getActivateError()) ? 'error' : 'success' ;
                ##
                $options['message'] = ['type '=> $type , 'content' => (array) $message] ;
            }

            if(is_array($options['message'])){
                ##
                if(! isset($options['message']['content']))    {
                    ##
                    $message = $options['message'] ;
                    unset($options['message']) ;
                    $options['message']['content'] = $message ;
                }
                ##
                $type = (true === $model->getActivateError()) ? 'error' : 'success' ;
                ##
                $options['message']['type'] = $type ;
            }
            ##
            $model->setMessage($options['message'])  ;
            ### set a default status code for error message
            if(! isset($options['status']))    {
                $model->setStatus(Response::STATUS_CODE_200) ;
            }
        }

        if(isset($options['status']))    {
            $model->setStatus($options['status']) ;
        }

        if(isset($options['extras']))    {
            $model->setAdditionalDetails($options['extras']) ;
        }
    }

    public function processInputFilter(InputFilter $inputFilter)
    {
        ###
        $imessages  = $inputFilter->getMessages() ;
        $model = $this->getDataModel()  ;

        if(null != $imessages)    {
            ##
            $model->setActivateError(true) ;
            $model->setType(self::CONTENT_TYPE_ERROR) ;
            $model->setStatus(Response::STATUS_CODE_500) ;

            $error_message['type'] = 'error' ;
            $error_message['content'] = [] ;

            if(is_array($imessages)) {
                ###
                foreach ($imessages as $element_name => $validator_n_msg) {
                    ##
                    foreach ($validator_n_msg as $validator_name => $message) {
                        ##
                        $error_message['content'][$element_name][$validator_name] = $message;
                    }
                }
            }
            ##
            $model->setMessage($error_message) ;
        }
    }

    public function setDataModel($dataModel)
    {
        $this->viewModel = $dataModel ;
        return $this;
    }

    /**
     * @return LogicResultModel
     */
    public function getDataModel()
    {
        if(null == $this->viewModel)    {
            $this->setDataModel(new LogicResultModel($this)) ;
        }
        return $this->viewModel ;
    }


    public function getActivateError()
    {
         return $this->getDataModel()->getActivateError() ;
    }
    public function getStatus()
    {
         return $this->getDataModel()->getStatus() ;
    }

    /**
     * Cast to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $result = $this->getDataModel() ;
        ## fetch all the view model variables
        $variables = $result->getVariables();
        ## extract the extras data and unset extras key bcos we don't want it to appear in the response body
        if(isset($variables['extras']))    {
            $extras = $variables['extras'] ;
            ## remove the extras key
            unset($variables['extras']) ;
            ## merge the extras without the key
            $variables = array_merge_recursive($variables , $extras) ;
        }

        if(isset($variables['status']))    {
            unset($variables['status']) ;
        }

        if(isset($variables['type']))    {
            unset($variables['type']) ;
        }

        if(isset($variables['error']))    {
            unset($variables['error']) ;
        }
        ## Required fields should always overwrite additional fields
        return $variables;
    }

    /**
     * Detect if a logic result contain an error
     */
    public function isError()
    {
        $model = $this->getDataModel() ;
        if($model->getActivateError())    {
            return true;
        }
        return false;
    }

    /**
     * Detect if a logic result contain an error
     */
    public function hasException()
    {
        $model = $this->getDataModel() ;
        if(null == $model->getException())    {
            return false;
        }
        return true;
    }

    /**
     * Detect if a logic result contain an error
     */
    public function getException()
    {
        $model = $this->getDataModel() ;
        return $model->getException();
    }

    /**
     * Merge two Logic Result Together
     * @param LogicResult $result
     * @return $this
     */
    public function merge(LogicResult $result)
    {
        ## fetch the data model
        $model = $this->getDataModel() ;
        ##
        $child_model = $result->getDataModel() ;
        ###
        $model->addChild($child_model) ;
        ###
        return $this ;
    }

    /**
     * Create detail message from an exception.
     *
     * @return string
     */
    protected function createDetailFromException(\Exception $exception)
    {
        $e = $exception;
        $this->additionalDetails['trace'] = $e->getTrace();
        $this->additionalDetails['traceAsString'] = $e->getTraceAsString();

        $previous = [];
        $e = $e->getPrevious();
        while ($e) {
            $previous[] = [
                'code' => (int) $e->getCode(),
                'message' => trim($e->getMessage()),
                'trace' => $e->getTrace(),
            ];
            $e = $e->getPrevious();
        }
        if (count($previous)) {
            $this->additionalDetails['exception_stack'] = $previous;
        }

        return $this->additionalDetails;
    }

    /**
     * Create HTTP status from an exception.
     *
     * @return int
     */
    protected function createStatusFromException($exception)
    {
        /** @var Exception|Throwable $e */
        $e = $exception;
        $status = $e->getCode();

        if (is_string($status) || is_numeric($status)) {
            return $status;
        }
        return 500;
    }
}