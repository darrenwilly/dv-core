<?php
namespace DV ;

use DV\Authentication\Api\Listener\{HeaderAuthentication , HeaderRequirement} ; 
use DV\ErrorHandler\ErrorHandler;
use DV\Router\Listener\ApachePatch;
use DV\View\Strategy\AutoDetectRenderer;
use Laminas\EventManager\EventInterface;
use DV\Cache\Engine ;

use Laminas\ServiceManager\ServiceManager;


class Module
{
    protected static $log ;

    public function init(ModuleManagerInterface $manager = null)
    {
        $eventManager = $manager->getEventManager() ;
        $eventManager->attach(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'initializeConfig'] , -10000);
    }

	public function onBootstrap(EventInterface $e)
	{
        $app = $e->getApplication() ;
		$eventManager        = $app->getEventManager();
		$serviceManager		 = $app->getServiceManager() ;
        
        ### set a class service manager function
        ServiceLocatorFactory::setMvcEvent($e);
        ServiceLocatorFactory::setInstance($serviceManager);

        ## initialize the log engine
        $this->initializeLog($e) ;
		
		$moduleRouteListener = new ModuleRouteListener();
		$moduleRouteListener->attach($eventManager);

        /** when a Laminas app is hosted in the folder structure like my own that allow multiple application of different platform
         * to sit inside one folder, I have a problem with apache which I cannot solve for now, it simply prepend the name of the
         * project master folder "backend" to the url. Later i will look for how to solve  the prepended folder name, but for now
         * The Class ApachePatch will correct the problem
         */
        $routePatch = new ApachePatch() ;
        $routePatch->attach($eventManager) ;

        /**
         * fix for situation where the listener logi will need url params before attaching but will not be found because the data is raw
         * NOTE: need to improved on bcos, I am still wonder why MVCEvent is already initiated at the point where this listener is attached
         */
        (new JsonToHttpPost())->convertJsonPostToHttpPost($e) ;

        $this->registerDoctrineUnknownDatatype($serviceManager) ;

		$view = new \DV\VolumeLicense\DV() ;
		$view->attach($eventManager) ;

        ## attach api listener only when an API call is detected since most of the logic within are for API call
        if ($this->isAPICall($app->getRequest())) {
            ## capture error at the global level
            $dvHandler = new ErrorHandler();
            $dvHandler->attach($eventManager);

            ## A CORS (Cross Origin Resource)
            $corListener = new CorsListener();
            $corListener->attach($eventManager) ;

            ## Convert Json Post to Http Post
           /* $corListener = new JsonToHttpPost();
            $corListener->attach($eventManager) ;*/

            ## event that handle how Http Header are loader
            $headerReq = new HeaderRequirement() ;
            $headerReq->attach($eventManager) ;

            ## event that handle how Http Header are loader
            $headerAuth = new HeaderAuthentication() ;
            $headerAuth->attach($eventManager) ;

            $authorization = new Authorization() ;
            $authorization->attach($eventManager) ;
            
            $apiReqResListener = new ApiRequestResponseAudit() ;
            #$apiReqResListener->attach($eventManager) ;
            
            ##
            $custom_response_listner = new LogicResultResponseListener();
            $custom_response_listner->attach($eventManager);

            $sendResponseListener = $serviceManager->get('SendResponseListener');
            $sendResponseListener->getEventManager()->attach(SendResponseEvent::EVENT_SEND_RESPONSE ,
                                    new SendLogicResultResponseListener(), -500);
        }

        ## get the view object in the App
        $viewManager = $serviceManager->get('View');
        ## fetch the Auto Detect Renderer class/object and attached at the highest of 1000 so that it is executed before any other view Rendering strategy
        $autoRenderer = $serviceManager->get(AutoDetectRenderer::TROJAN_STRATEGY) ;
        $autoRenderer->attach($viewManager->getEventManager() , 1000);
	}


    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
    
    /*
     * (non-PHPdoc)
     * @see \Laminas\ModuleManager\Feature\ServiceProviderInterface::getServiceConfig()
     */
    public function getServiceConfig()
    {
    	return [
    	    'aliases' => [
    	       'get_auth_service' => 'getAuthService'
            ] ,
    		'invokables' => [

    		] ,
    			
    		'factories' => [
                    'getAuthService' => function ($sm)	{
                        /**
                         * initiate the DV Authentication Service Class
                         * @var \DV\Authentication\Authentication $auth_service
                         */
                        #$auth_service = new c_auth_service() ;
                        $auth_service = new \DV\Authentication\Authentication() ;
                        ### fetch the auth service instance and set the default storage
                        #$auth_service->get_auth_service()->setStorage($session_storage) ;

                        return $auth_service ;
                    }  ,
    				###
    				'Filesystem\Cache\Engine' => function ($sm) {
    					### fetch the Filesystem cache
    					$_cache = new \Laminas\Cache\Storage\Adapter\Filesystem() ;
    					
    					$serialize_plugin = new \Laminas\Cache\Storage\Plugin\Serializer();
    					### add the plugin
    					$_cache->addPlugin($serialize_plugin);
    						
    					$exception_plugin = new \Laminas\Cache\Storage\Plugin\ExceptionHandler() ;
    					$exception_plugin->getOptions()->setThrowExceptions(true) ;
    					### add the exception plugin
    					$_cache->addPlugin($exception_plugin) ;
    					
    					return $_cache ;
    				} ,
    				
    				###    				
    				'DV\Service\FlashMessanger\Cache' => function($sm)	{
    					### 
    					$_options['adapter'] = Engine::$_adapterOptions ;
    					$_options['adapter']['ttl'] = 86400 ;
    					$_options['adapter']['cache_dir'] = call_user_func(function () {
    					    ##
    					    $dir = APPLICATION_CACHE_DIR. '/system_message' ;
    					    ##
                            if(! is_dir($dir))    {
                                mkdir($dir , 0766 , true) ;
                            }
                            return realpath($dir) ;
                        }) ;
    					
    					$_cache = $sm->get('Filesystem\Cache\Engine') ;
    					### set the adapter Options
    					$_cache->setOptions($_options['adapter']) ;
    					
    					return $_cache ;
    				} ,
    				
    				'DV\View\Helper\html\Cache' => function($sm)	{
    					### create the option configuration to pass unto cache
    					
    					$_options['adapter'] = Engine::$_adapterOptions ;
    					$_options['adapter']['ttl'] = 86400 ;
                        $_options['adapter']['cache_dir'] = call_user_func(function () {
                            ##
                            $dir = APPLICATION_CACHE_DIR. '/data_uri' ;
                            ##
                            if(! is_dir($dir))    {
                                mkdir($dir , 0766 , true) ;
                            }
                            return realpath($dir) ;
                        }) ;
    					
    					$_cache = $sm->get('Filesystem\Cache\Engine') ;
    					### set the adapter Options
    					$_cache->setOptions($_options['adapter']) ;   				
    					
    					return $_cache ;
    				} , 

    		] ,
    	] ;
    }


    private function registerDoctrineUnknownDatatype(ServiceManager $sm)
    {
        /**
         * DOCTRINE ENTITY MANAGER
         * @var \Doctrine\ORM\EntityManager $platform
         */
        $platform = $sm->get('Doctrine\ORM\EntityManager') ;

        $conn = $platform->getConnection();
        $conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        /* $config = $platform->getConfiguration() ; */
    }

    public function initializeConfig(ModuleEvent $e)
    {
        $config = ($e->getConfigListener()->getMergedConfig()) ;
        ###
        $phpSettings = $config->get('phpSettings') ;
        ###
        if($phpSettings instanceof \Iterator)    {
            foreach($phpSettings as $name => $setting) {
                ## we want to override other session handlers here...
                ini_set($name , $setting);
            }
        }

    }

    /**
     * @return \Laminas\Log\Logger
     */
    public static function Log()
    {
        return self::$log;
    }

    public function initializeLog($e)
    {
        $reportLogger = (new \DV\Log\Reporter()) ;
        ##
        $prepareJsonWriter = function() {
            #
            $streamWriter = new \DV\Log\Writer\Stream(['logFileMode' => 'json']) ;
            ##
            #$streamWriter = $streamWriter->getWriter() ;
            #$streamWriter->setFormatter(new \Laminas\Log\Formatter\Json()) ;
            #$streamWriterItself->setFormatter($streamWriter->getSimpleFormatter()) ;
            ##
            return $streamWriter ;
        } ;

        $logger = $reportLogger->getLogger() ;
        ##
        $logger->addWriter(call_user_func($prepareJsonWriter)) ;
        ##
        $logger::registerErrorHandler($logger) ;
        $logger::registerExceptionHandler($logger) ;

        ##
        self::$log = $logger ;
    }
}
