<?php
declare(strict_types=1);

namespace DV\Mvc\EventSubscriber;

use DV\Mvc\Controller\AuthenticatedActionController;
use DV\Mvc\Controller\AuthenticatedActionControllerException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AuthenticatedActionEventSubscriber implements EventSubscriberInterface
{

    /**
     * Check if the User has been authenticated.
     *
     * @throws \Exception
     * @internal param array $redirector
     */
    public function _check_authentication(ControllerArgumentsEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        #$controllerObject = new \ReflectionFunction(\Closure::fromCallable($event->getController()));
        $eventController = $event->getController() ;
        /**
         * extract the Controller Object out of the event
         */
        if(is_array($eventController) && is_callable($eventController))    {
            ##
            list($controllerObject , $actionString) = $eventController ;
        }
        elseif (is_object($eventController))    {
            $controllerObject = $eventController ;
        }

        #$controllerReflectionObject = new \ReflectionObject($controllerObject) ;
        #dump($controllerReflectionObject->getParentClass()) ;exit;
        /**
         * Only Controller that has extend the AuthenticatedActionController will be considered in this logic
         */
        if(! $controllerObject instanceof AuthenticatedActionController)    {
            return ;
        }

        if(! $controllerObject->getUserInfo())    {
            ##generate the login route to redirect to
            $response = new RedirectResponse('/login') ;
            ##
            $authControllerException = new AuthenticatedActionControllerException('You are required to be authenticated to access the resource, Please login and try again') ;
            $authControllerException->setController($controllerObject) ;
            $authControllerException->setEvent($event) ;
            ##
            throw new $authControllerException ;
        }
    }

    static public function getSubscribedEvents()
    {
        $event = [
            KernelEvents::CONTROLLER_ARGUMENTS => [
                ['_check_authentication' , 10000]
            ]
        ] ;
        return $event ;
    }
}