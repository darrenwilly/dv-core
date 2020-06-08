<?php
declare(strict_types=1);
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator ;


return function(ContainerConfigurator $configurator)   {
    ## default configuration for services in *this* file
    $services = $configurator->services()
        ->defaults()
        ->autowire()      // Automatically injects dependencies in your services.
        ->autoconfigure() // Automatically registers your services as commands, event subscribers, etc.
        ->public()
    ;

    try{
        /**
         * Implement the fuction of AuthenticatedActionController by checking for Authentication on Controller that extend AuthenticatedActionController class
         */
        $services->set(\DV\Mvc\EventSubscriber\AuthenticatedActionEventSubscriber::class)->tag('kernel.event_subscriber');
        $services->set(\DV\Mvc\EventSubscriber\AuthenticatedActionExceptionEventSubscriber::class)->tag('kernel.event_subscriber');

        $services->set(\DV\Mvc\Controller\ActionController::class)->tag('controller.service_arguments');
        $services->set(\DV\Mvc\Controller\AuthenticatedActionController::class)->tag('controller.service_arguments') ;

        /**
         * Register the FlashMessenger Cache Service
         */
        $services->set(\DV\Service\FlashMessenger\CacheInterface::class , \DV\Service\FlashMessenger\CacheFactory::class)
                    ->arg('$redisClient' , new \Symfony\Component\DependencyInjection\Reference('cache.redis.default'));

        /**
         * LOAD Validator

        $services->load('DV\\Validator\\' , dirname(__DIR__).'/src/Validator/*')
                            ->tag('validator.constraint_validator') ; */

    }
    catch (\Throwable $exception)   {
            dump($exception); exit;
    }
    ##
    return $services;
};