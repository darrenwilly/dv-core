<?php
namespace DV\Service ;

use DV\ContainerService\ServiceLocatorFactory;
use DV\MicroService\TraitContainer;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Form\Form;
use Laminas\InputFilter\InputFilterInterface;
use DV\MicroService\BaseTrait ;
use Exception ;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

trait FlashMessenger
{
    use BaseTrait;
    use TraitContainer ;

	public static $FLASH_MESSENGER_NAMESPACE = 'dv_veiw_flashMessages' ;

    public static $URI_MESSAGE_KEY = 'sysmsg' ;

    /**
     * @var FlashBag
     */
    protected $flashMessenger;
    protected $flashStorage;
    protected $request ;

    public function __construct(RequestStack $request , ContainerInterface $container)
    {
        if($request instanceof RequestStack)    {
            $request = $request->getCurrentRequest() ;
        }

        $this->request = $request ;
        ##
        $this->flashStorage = $request->getSession()->getFlashBag() ;
        $this->flashMessenger = $this ;
        $this->setContainer($container) ;
    }

    /**
     * @return $this
     */
    public function flashMessengerEngine()
    {   ##
        return $this  ;
    }

    /**
     * @return FlashBag
     */
    public function getFlashStorage()
    {
        return $this->flashStorage ;
    }

    public function addFlashMessage(array $message_n_key)
    {
        foreach ($message_n_key as $key => $value)  {
            ##
            $this->getFlashStorage()->add($key , $value)  ;
        }
        unset($message_n_key);
    }

    public function getMessages($key='error')
    {
        $message_as_string = null ;
        ### fetch the flash message
        $flashMessenger = $this->getFlashStorage();
        ### get messages from previous requests
        $all_flash_messages = $flashMessenger->all();
        ###
        if(0 < count($all_flash_messages)) {
            $output = '';

            $message_repeater_remover = [] ;
            ### process messages
            foreach ($all_flash_messages as $message) {
                ### fetch each message(key & value) from the $message
                $key = key($message) ;
                $message_body = $message[$key] ;

                if (in_array($key, ['success', 'info', 'notice'])) {
                    $key = 'success';
                } else {
                    $key = 'error';
                }

                ### unset repeatition message
                $message_as_string[$key][] = $message_body ;
            }
        }
        ##
        return $message_as_string ;
    }

    /**
     * Static Bind call to the message
     * @param $messageCode
     * @param null $sprintf
     * @throws Exception
     */
    static public function message($messageCode , $sprintf=null)
    {
        ##
        $container = ServiceLocatorFactory::getInstance() ;
        ##
        $self = new self($container->get('request_stack') , $container) ;
        return $self->_message($messageCode , $sprintf) ;
    }

	/**
	 * proxy call to internal message method
	 *
	 * @param int|numeric $messageCode|key
	 * @param string|array $sprintf|message
	 *
	 * @return
	 */
	public function _message($messageCode , $sprintf=null)
	{
		$this->setModel($this->getContainer()->get(\Veiw\Core\Query\System::class));

		if(is_numeric($messageCode))    {
			### fetch the message from the db
			$model = $this->getModel() ;
			
			### cache the url data info on production environment
			if(PRODUCTION == APPLICATION_ENV)	{
                $result_bool = null ;
                ##
                $cacheSystem = $this->_getCache() ;
				### try to fetch already save message from catch
				$message_entity_row_cache_hit = $cacheSystem->getItem($messageCode) ;
				##
                if(! $message_entity_row_cache_hit->isHit())    {
                    ## fetch the message
                    $message_entity_row = $model->get_system_message(['row' => ['code' => $messageCode , 'activated' => ActionControl::YES]]) ;
                    ##
                    $message_entity_row_cache_hit->set($message_entity_row) ;
                    ##
                    $cacheSystem->save($message_entity_row_cache_hit) ;
                }
                else{
                    $message_entity_row = $message_entity_row_cache_hit->get() ;
                }
			}
			else{
				### fetch the message
				$message_entity_row = $model->get_system_message(['row' => ['code' => $messageCode , 'activated' => ActionControl::YES]]) ;
			}						

			### if no message was fetch, return custom error
			if(null == $message_entity_row)	{
				$key = 'error' ;
				$message = 'invalid message criteria supplied, unable to locate the message from database' ;
			}
			else{
				### fetch the parameters from db
				$key = $message_entity_row->getType() ;
				### allow the message to use sprintf incase the message has placeholder
				if(null != $sprintf)	{
					### cast sprintf as array
					$sprintf = (array) $sprintf ;
					### format the output
					$message = vsprintf($message_entity_row->getMessage() , $sprintf) ;
				}
				else{### message without sprintf format
					$message = $message_entity_row->getMessage() ;
				}
			}
			
			$message_n_key = [$key => $message] ;

			return $this->addFlashMessage($message_n_key) ;
		}
		else{
			$key = (string) $messageCode ;
			$message = (string) $sprintf ;
			
			$message_n_key = [$key => $message] ;
			### save the message in the flash messenger
			return $this->addFlashMessage($message_n_key);
		}
	}

    public function getFormErrorMessages($form)
    {
        if(! $form instanceof Form)    {
            throw new \RuntimeException('Instance of Symfony\Form Expected, instance of '.gettype($form).' passed') ;
        }
        ###
        $messages  = $form->getErrors() ;
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
            throw new Exception('Instance of Laminas\Form Expected, instance of '.gettype($iFilter).' passed') ;
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
	
	
	static public function flashMessageFromUri($_params)
	{
		$_url_params = $_params[self::$URI_MESSAGE_KEY] ;
			
		### check for system message in the url string
		if(null != $_url_params)    {
			### make sure the value to be passed to flash message is a string
			if(is_numeric($_url_params))    {
				self::message($_url_params) ;
			}
		}
	}

	/**
	 * Fetch the cache engine
	 *
	 * @return FilesystemAdapter|\Symfony\Component\Cache\Adapter\RedisAdapter
	 */
	protected function _getCache()
	{
	    if(! $this->getContainer()->has(\DV\Service\FlashMessenger\CacheInterface::class))    {
	        throw new \RuntimeException('DV\Service\FlashMessenger\Cache is not defined in container service') ;
        }
		### create the option configuration to pass unto cache
		$cache = $this->getContainer()->get(\DV\Service\FlashMessenger\CacheInterface::class) ;
		##
	    return $cache->getCacheSystem() ;
	}


	public function hasCurrentMessages()
    {
        $flash_engine = $this->getFlashStorage() ;
        ##
        return (0 < count($flash_engine->all())) ;
    }


}