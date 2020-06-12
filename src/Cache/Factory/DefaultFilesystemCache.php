<?php
declare(strict_types=1);

namespace DV\Cache\Factory;

use DV\Cache\Engine;
use Psr\Container\ContainerInterface;

class DefaultFilesystemCache
{

    public function __invoke(ContainerInterface $container)
    {
        $_options['adapter'] = Engine::$_adapterOptions ;
        $_options['adapter']['ttl'] = 86400 ;
        $_options['adapter']['cache_dir'] = call_user_func(function () {
            ##
            $dir = APPLICATION_CACHE_DIR. '/filesystem' ;
            ##
            if(! is_dir($dir))    {
                mkdir($dir , 0766 , true) ;
            }
            return realpath($dir) ;
        }) ;

        $_cache = $container->get('Filesystem\Cache\Engine') ;
        ## set the adapter Options
        $_cache->setOptions($_options['adapter']) ;

        return $_cache ;
    }
}