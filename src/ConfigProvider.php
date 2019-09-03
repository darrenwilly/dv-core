<?php
declare(strict_types=1);
namespace DV ;

use DV\Cache\Factory\DefaultFilesystemCache;
use DV\Cache\Factory\FilesystemCacheEngineFactory;
use DV\Expressive\ConfigProviderBootstrapInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Zend\EventManager\EventManagerInterface;
use DV\EventManager\EventManagerFactory ;


class ConfigProvider implements ConfigProviderBootstrapInterface
{

    public function bootstrap(ContainerInterface $container ,  RequestInterface $request) : void
    {

    }

    public function __invoke() : array
    {
        $config = [
            'dependencies' => $this->getDependencies(),
        ];

        return $config ;
    }

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
    
    /*
     * (non-PHPdoc)
     */
    public function getDependencies()
    {
    	$config = [
    	    'aliases' => [

            ] ,
    		'invokables' => [

    		] ,
    		'factories' => [
                #event manager
                EventManagerInterface::class => EventManagerFactory::class ,
    		    ##
                'Filesystem\Cache\Engine' => FilesystemCacheEngineFactory::class ,
    			 ##
                'DF\Filesystem\Cache' => DefaultFilesystemCache::class ,
            ] ,

    	] ;
    	##
        return $config ;
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
     * @return \Zend\Log\Logger
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
