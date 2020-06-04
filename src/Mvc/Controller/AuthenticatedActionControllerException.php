<?php
declare(strict_types=1);

namespace DV\Mvc\Controller ;

use Throwable;

class AuthenticatedActionControllerException extends \RuntimeException
{
    protected $controller ;
    protected $event ;

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function setController($controller)
    {
        $this->controller = $controller ;
    }
    public function getController()
    {
        return $this->controller ;
    }

    public function setEvent($event)
    {
        $this->event = $event ;
    }
    public function getEvent()
    {
        return $this->event ;
    }

}
