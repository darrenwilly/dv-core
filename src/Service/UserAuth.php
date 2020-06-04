<?php
namespace DV\Service;


trait UserAuth
{
    protected static $AUTH_SERVICE_NAME = 'getAuthService' ;

    /**
     * Get user info from the auth session
     *
     * @param null $options
     * @return string
     * @internal param null|string $info The data to fetch, null to chain
     */
    public function getUserInfo($options=[])
    {
        ## check for value in options
        if (null == $options || (is_array($options) && null == count($options)) ) {
            return $this->hasLoggedIn();
        }

        ### before loading the information form identity, verify the user has signed in
        if (false === $this->has_logged_in()) {
            return false ;
        }

        ##
        $auth_service = $this->security;
        ##
        $user_entity_row = $auth_service->getUser() ;

        if(is_array($options))	{
            ##
            $info = null ;
            ##
            foreach ($options as $option)		{
                ##
                $underScoreToCamelCase = new \Laminas\Filter\Word\UnderscoreToCamelCase() ;
                ##
                $user_options = sprintf('get%s'  , ucfirst($underScoreToCamelCase->filter($option))) ;
                ##
                $info[] = $user_entity_row->{$user_options}() ;
            }
            ##
            return $info ;
        }

        if(is_string($options))	{
            ### check if the key is "getIdentity"
            if('getIdentity' == $options)	{
                return $user_entity_row->getIdentity() ;
            }

            if('getEntity' == $options || 'entity' == $options)	{
                return $user_entity_row ;
            }
            ### initiate the underscore to camelcase filter
            $underScoreToCamelCase = new \Laminas\Filter\Word\UnderscoreToCamelCase() ;
            ##
            $user_options = sprintf('get%s'  , ucfirst($underScoreToCamelCase->filter($options))) ;
            $options = 'get'.ucfirst($underScoreToCamelCase->filter($options)) ;
            ##
            return $user_entity_row->{$options}();
        }

        ### load the identity object only when Identity has been registered
        if(! $this->has_logged_in())	{
            return false ;
        }
        ##
        return $user_entity_row->{$options}();
    }


    /**
     * Check if we are logged in
     *
     * @return boolean
     */
    public function has_logged_in()
    {
        return $this->hasLoggedIn();
    }
    public function hasLoggedIn()
    {
        ##
        $auth_service = $this->security;
        ##
        $user_entity_row = $auth_service->getUser() ;
        ##
        if(empty($user_entity_row))     {
            ##
            return false ;
        }

        ##
        $acl = $this->security->isGranted('IS_AUTHENTICATED_FULLY');
        ##
        return $acl ;
    }
}