<?php
declare(strict_types=1);

namespace DV\Cache\Factory;

use Psr\Container\ContainerInterface;

class FilesystemCacheEngineFactory
{

    public function __invoke(ContainerInterface $container)
    {
        $cache = new \Zend\Cache\Storage\Adapter\Filesystem() ;

        $serialize_plugin = new \Zend\Cache\Storage\Plugin\Serializer();
        ### add the plugin
        $cache->addPlugin($serialize_plugin);

        $exception_plugin = new \Zend\Cache\Storage\Plugin\ExceptionHandler() ;
        $exception_plugin->getOptions()->setThrowExceptions(true) ;
        ## add the exception plugin
        $cache->addPlugin($exception_plugin) ;

        return $cache ;
    }
}