<?php
namespace DV\Service;

use DV\Mvc\Service\ServiceLocatorFactory ;

trait UserAuth
{
    protected static $AUTH_SERVICE_NAME = 'getAuthService' ;
    /**
     * Return some User information that is provided as Key.
     *
     * @params string key to the parament.
     * @param null $key
     * @return \
     */
    public function getUserInfo($key=null)
    {
        $auth_service = ServiceLocatorFactory::getLocator(self::$AUTH_SERVICE_NAME) ;
        if(null == $key)	{
            return $auth_service->hasIdentity() ;
        }

        if(is_string($key))	{
            ### check if the key is "getIdentity"
            if('getIdentity' == $key)	{
                return $auth_service->getIdentity() ;
            }

            if('clearIdentity' == $key)	{
                return $auth_service->clear() ;
            }
            ### initiate the underscore to camelcase filter
            $underScoreToCamelCase = new \Zend\Filter\Word\UnderscoreToCamelCase() ;
            ### filter the identity
            $getEntityMethod = 'get'.ucfirst($underScoreToCamelCase->filter($key)) ;
            $identity = $auth_service->getIdentity() ;

            ## when entity object is stored
            if(method_exists($identity , $getEntityMethod))    {
                ### return the property of User table
                return $identity->{$getEntityMethod}() ;
            }
            elseif(property_exists($identity , $key))   {
                ##
                $identityReflection = new \ReflectionObject($identity) ;
                return $identityReflection->getProperty($key) ;
            }
        }

        throw new \UnexpectedValueException('We cannot ascertain / fetch any required object matching your desire') ;
    }

    public function clearIdentity()
    {
        $auth_service = ServiceLocatorFactory::getLocator(self::$AUTH_SERVICE_NAME);
        return $auth_service->clear() ;
    }

    public static function staticGetUserInfo($key=null)
    {
        $self = new self() ;

        return $self->getUserInfo($key) ;
    }
}