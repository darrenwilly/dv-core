<?php
namespace DV\Service;

use Zend\Session ;
use DV\Session\Session as primary_class;

trait SessionX
{
    protected $namespace ;

    public function getSession()
    {
        $session = new primary_class() ;
        return $session ;
    }

    public function getContainer($namespace=null)
    {
        ### try to overwrite the namespace
        if(null == $namespace)    {
            $namespace = $this->getNamespace() ;
        }
        return $this->getSession()->getContainer($namespace) ;
    }

    public function getNamespace()
    {
        if(null == $this->namespace)    {
            $this->setNamespace(PROJECT_NAME.DARRISM.WEBCREATIVE) ;
        }
        return $this->namespace ;
    }
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace ;
        return $this;
    }

    public function sessionStore($key , $data)
    {
        return $this->getContainer()->offsetSet($key , $data) ;
    }

    public function sessionRetrieve($key)
    {
        return $this->getContainer()->offsetGet($key) ;
    }

    public function sessionCheck($key)
    {
        return $this->getContainer()->offsetExists($key) ;
    }

    public function sessionForget($key=null)
    {
        if(null != $key) {
            return $this->getContainer()->offsetUnset($key);
        }
        return $this->getSession()->clear() ;
    }

    public function sessionHops($key , $hops)
    {
        $object = $this->getContainer();
        $object->setExpirationHops($hops , $key);
    }
    public function sessionMinutes($key , $mins)
    {
        $object = $this->getContainer();
        $object->setExpirationSeconds($mins , $key);
    }


}