<?php
declare(strict_types=1);
namespace DV\Service\FlashMessenger;

use DV\Service\FlashMessenger;
use Symfony\Component\Cache\Adapter\FilesystemAdapter ;

class CacheFactory implements CacheInterface
{
    protected $cacheSystem;

    public function __construct($redisClient)
    {
        try{
            #$redisClient = RedisAdapter::createConnection($container->);
            ##
            #$cacheSystem = new \Symfony\Component\Cache\Adapter\RedisAdapter($redisClient , FlashMessenger::$FLASH_MESSENGER_NAMESPACE) ;
            $cacheSystem = $redisClient ;

        }
        catch (\Throwable $exception)   {
            ## create the cache system
            $default_directory = APPLICATION_CACHE_DIR ;
            $cacheSystem = new \Symfony\Component\Cache\Adapter\FilesystemAdapter(FlashMessenger::$FLASH_MESSENGER_NAMESPACE , 86400 , $default_directory);
        }

        $this->cacheSystem = $cacheSystem ;
    }

    /**
     * @return FilesystemAdapter|\Symfony\Component\Cache\Adapter\RedisAdapter
     */
    public function getCacheSystem()
    {
        return $this->cacheSystem ;
    }
}