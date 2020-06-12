<?php
declare(strict_types=1);

namespace DV\Mvc\EventSubscriber;

use DV\Mvc\Controller\AuthenticatedActionControllerException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AuthenticatedActionExceptionEventSubscriber implements EventSubscriberInterface
{
    /**
     * Check if the User has been authenticated.
     *
     * @throws \Exception
     * @internal param array $redirector
     */
    public function onKernelException(ExceptionEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {

        $controllerException = $event->getThrowable() ;
        /**
         * Only Controller that has extend the AuthenticatedActionController will be considered in this logic
         */
        if(! $controllerException instanceof AuthenticatedActionControllerException)    {
            return ;
        }
        ##
        $event->allowCustomResponseCode();
        ##
        $response = new RedirectResponse('/login');

        // setup the Response object based on the caught exception
        $event->setResponse($response);
        return $event;
    }

    static public function getSubscribedEvents()
    {
        $event = [
            KernelEvents::EXCEPTION => [
                ['onKernelException' , 10000]
            ]
        ] ;
        return $event ;
    }
}