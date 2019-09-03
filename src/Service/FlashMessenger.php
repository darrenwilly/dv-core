<?php
namespace DV\Service ;

use DV\Json\Validate as jsonValidator;
use Zend\Form\Form;
use Zend\InputFilter\InputFilterInterface;
use Zend\Mvc\Plugin\FlashMessenger\FlashMessenger as zend_flash_messenger ;
use DV\Mvc\Service\ServiceLocatorFactory ;
use Zend\Di\ServiceLocator;
use DV\Model\BaseTrait ;
use Exception ;

trait FlashMessenger
{
    use BaseTrait;
    use jsonValidator ;

	public static $FLASH_MESSENGER_NAMESPACE = 'dv_veiw_flashMessages' ;

    public static $URI_MESSAGE_KEY = 'sysmsg' ;

    protected $flash_messenger;



    public function flashMessengerEngine()
    {
        if(null == $this->flash_messenger)    {
            $this->flash_messenger = new zend_flash_messenger() ;
            $this->flash_messenger->setNamespace(self::$FLASH_MESSENGER_NAMESPACE) ;
        }

        ##
        return $this->flash_messenger  ;
    }

    public function addFlashMessage(array $message_n_key)
    {
        $flash_message = $this->flashMessengerEngine()->addMessage($message_n_key)  ;
        return $flash_message ;
    }

    public function getFlashMessage($key='error')
    {
        $message_as_string = null ;
        ### fetch the flash message
        $flashMessenger = $this->flashMessengerEngine();
        ### get messages from previous requests
        $all_flash_messages = $flashMessenger->getMessagesFromNamespace(self::$FLASH_MESSENGER_NAMESPACE);

        $all_flash_messages = array_merge($all_flash_messages , $flashMessenger->getCurrentMessages());
        ###
        if($flashMessenger->hasMessages()) {
            $output = '';

            $message_repeater_remover = [] ;
            ### process messages
            foreach ($all_flash_messages as $message) {

                if (is_array($message)) {
                    ### fetch each message(key & value) from the $message
                    list($key , $message_body) = each($message);

                    ### force the exception key to be translated to error incase, we are using exception message for flash messenger
                    /*if (in_array($key, ['exception', 'error' , ''])) {
                        $key = 'error';
                    } else*/
                    if (in_array($key, ['success', 'info', 'notice'])) {
                        $key = 'success';
                    } else {
                        $key = 'error';
                    }

                    ### set the right response header is error message is sent
                    if($this->giveMeJson() && in_array($key , ['exception' , 'error']))    {
                        ## add the json error response header
                        $this->jsonErrorHeaders() ;
                    }

                    if (! in_array($message_body , $message_repeater_remover)) {
                        ### unset repeatition message
                        $message_as_string[$key][] = $message_body ;
                        ## add the message inside the repeater_remover variable so that it does not appear twice
                        $message_repeater_remover[] = $message_body;
                    }
                }
            }
        }
        ##
        return $message_as_string ;
    }

	/**
	 * proxy call to internal message method
	 *
	 * @param int|numeric $messageCode|key
	 * @param string|array $sprintf|message
	 *
	 * @return zend_flash_messenger
	 */
	static public function message($messageCode , $sprintf=null)
	{
		$self = new self() ;
		$self->setModel(new \DV\Model\System());

		if(is_numeric($messageCode))    {
			### fetch the message from the db
			$model = $self->getModel() ;
			
			### cache the url data info on production environment
			if(PRODUCTION == APPLICATION_ENV)	{
                $result_bool = null ;
				### try to fetch already save message from catch
				$message_row = $self->_getCache()->getItem($messageCode , $result_bool) ;
				
				### check for the available cache
				if(! ($result_bool)) 	{
					### fetch the message
					$message_row = $model->get_system_message(['row' => ['code' => $messageCode , 'activated' => ActionControl::YES]]) ;
					### save the message fetch from db.
                    $self->_getCache()->setItem($messageCode , $message_row);
				}
			} else{
				### fetch the message
				$message_row = $model->get_system_message(['row' => ['code' => $messageCode , 'activated' => ActionControl::YES]]) ;
			}						
			
			### if no message was fetch, return custom error
			if(null == count($message_row))	{
				$key = 'error' ;
				$message = 'invalid message criteria supplied, unable to locate the message from database' ;
			}
			else{
				### fetch the parameters from db
				$key = $message_row->getType() ;
				### allow the message to use sprintf incase the message has placeholder
				if(null != $sprintf)	{
					### cast sprintf as array
					$sprintf = (array) $sprintf ;
					### format the output
					$message = vsprintf($message_row->getMessage() , $sprintf) ;
				}
				else{### message without sprintf format
					$message = $message_row->getMessage() ;
				}
			}
			
			$message_n_key = [$key => $message] ;
			
			return $self->addFlashMessage($message_n_key) ;
		}
		else{
			$key = (string) $messageCode ;
			$message = (string) $sprintf ;
			
			$message_n_key = [$key => $message] ;
			### save the message in the flash messenger
			return $self->addFlashMessage($message_n_key);
		}
	}

    public function getFormErrorMessages($form)
    {
        if(! $form instanceof Form)    {
            throw new Exception('Instance of Zend\Form Expected, instance of '.gettype($form).' passed') ;
        }
        ###
        $messages  = $form->getMessages() ;
        ###
        foreach ($messages as $element_name => $validator_n_msg) {
            ###
            foreach ($validator_n_msg as $validator => $message) {
                ###
                $this->addFlashMessage(['error' => $element_name.' : '. $validator .' :- '. $message]) ;
            }
        }
    }

    public function getIFilterErrorMessages($iFilter)
    {
        if(! $iFilter instanceof InputFilterInterface)    {
            throw new Exception('Instance of Zend\Form Expected, instance of '.gettype($iFilter).' passed') ;
        }
        ###
        $messages  = $iFilter->getMessages() ;
        ###
        foreach ($messages as $element_name => $validator_n_msg) {
            ###
            foreach ($validator_n_msg as $validator => $message) {
                ###
                $this->addFlashMessage(['error' => $element_name.' : '. $validator .' :- '. $message]) ;
            }
        }
    }
	
	
	static public function flash_message_from_uri($_params)
	{
		$_url_params = $_params[self::URI_MESSAGE_KEY] ;
			
		### check for system message in the url string
		if(null != $_url_params)    {
			### make sure the value to be passed to flash messager is a string
			if(is_numeric($_url_params))    {
				self::message($_url_params) ;
			}
		}
	}

	/**
	 * Fetch the cache engine
	 *
	 * @return \Zend\Cache\Storage\StorageInterface
	 */
	protected function _getCache()
	{
		### create the option configuration to pass unto cache
		$_cache = ServiceLocatorFactory::getLocator('DV\Service\FlashMessenger\Cache') ;
		return $_cache ;
	}
}