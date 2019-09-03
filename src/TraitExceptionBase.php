<?php
namespace DV;

use DV\Expressive\Service\ContainerFactory;
use Zend\Stdlib\Parameters;

trait TraitExceptionBase
{
    protected static $SUFFIX = 'please contact the Application Support Group' ;
    #protected $logicIdentifier ;

    public function processMessage($message) : string
    {
        if(method_exists($this , 'getContainer'))    {
            ##
            $container = $this->getContainer() ;
            $mergeConfig = $container->get('config') ;
            ## fetch the supportConfig
            $supportConfig = $mergeConfig['supportConfig'] ;
            ##
            $supportInfo = (new Parameters($supportConfig))->toString() . PHP_EOL ;
        }else{
            ##
            $globalConfigFile = GLOBAL_CONFIG_PATH . '/autoload/global.php' ;

            if(file_exists($globalConfigFile))    {
                ##
                clearstatcache(GLOBAL_CONFIG_PATH) ;
                ##
                $mergeConfig = require $globalConfigFile;
                ##
                $supportConfig = $mergeConfig['supportConfig'] ;
                ##
                $supportInfo = (new Parameters($supportConfig))->toString() . PHP_EOL ;
            }

        }

        $logicIdentifier = (null == $this->logicIdentifier) ? get_class() : $this->logicIdentifier ;

        $finalMessage = $message . PHP_EOL ;
        $finalMessage .= '  
        ';
        $finalMessage .= 'identifier: ' .$logicIdentifier. PHP_EOL ;
        $finalMessage .= '  
        ';
        $finalMessage .= '  '.self::$SUFFIX . PHP_EOL ;
        $finalMessage .= '  
        ';
        $finalMessage .= (isset($supportInfo)) ? $supportInfo : PHP_EOL ;
        ##
        return $finalMessage ;
    }

    public function setIdentifier($identifier)
    {
        $this->logicIdentifier = $identifier ;
    }
}