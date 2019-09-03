<?php

namespace DV\Session ;

use DV\Mvc\Service\ServiceLocatorFactory ;
use Zend\Session\Config\StandardConfig;
use Zend\Session\Storage ;


class Config 
{

	protected $_config ;
		
	protected static $_default_config = array(
	    'remember_me_seconds' => 2419200,
        'use_cookies'       => true,
        'cookie_httponly'   => true,
        'cookie_domain'     => 'darrismsolutions.com',
        'name'              => 'veiw' ,
        'save_path' => APPLICATION_DATA_PATH.DIRECTORY_SEPARATOR.'session'
    ) ;
	
	protected $_storage ;	
	
	protected $_validator ;
	
	protected $_save_handler ;
	
	
	public function __construct()
	{
		$_sm = ServiceLocatorFactory::getInstance() ;
		$_sm_config = $_sm->get('config') ;

        ### fetch the session config from key
        $_sm_config_session = $_sm_config['session'] ;
			
		if(array_key_exists('options', $_sm_config_session['config']) && is_array($_sm_config_session['config']['options']))	{
              $_options = $_sm_config_session['config']['options'] ;
		}
		else {
             ### fetch the default config
             $_options = self::$_default_config ;
		}
		##
        self::createSessionDirectory($_options);
		##
        if(array_key_exists('class', $_sm_config_session['config']))	{
                $_class = $_sm_config_session['config']['class'] ;
                ### initiate the class string as object
                $session_config_class = new $_class();
		}
		else {
			  ### initiate the session config file
               $session_config_class = new StandardConfig();
		}

        ### set the options for the config class
        $session_config_class->setOptions($_options) ;

        ### set the instance of Zend_Config class as the Session config object to be used in SessionManager
        $this->setConfig($session_config_class) ;

        /**
         * configure the storage class
         */
        if(array_key_exists('storage', $_sm_config_session))	{
        	### fetch the storage class
        	$storage_class = $_sm_config_session['storage'] ;
        	### create the storage class object
        	$storage_class_object = new $storage_class() ;        	
        }
		else{
			$storage_class_object = new Storage\SessionArrayStorage() ;
		}
		
		
		if(isset($storage_class_object))	{
			$this->setStorage($storage_class_object) ;
		}		
		
		/**
		 * configure the validators and set them
		 */
		if(array_key_exists('validators', $_sm_config_session))	{
			$this->setValidator($_sm_config_session['validators']) ;
		}
		
		#return $this ;
	}		
		
		
		
	public function getConfig()
	{
         return $this->_config ;
	}
	public function setConfig($_config)
	{			
         $this->_config = $_config ;
	}	
		
	/**
	 * fetch the zend session configuration storage
	 * @return \Zend\Session\Storage
	 */	
	public function getStorage()
	{
         return $this->_storage;
	}	
	public function setStorage($_storage)
	{            	
         $this->_storage = $_storage ;
	}
		
		
	public function getValidator()
	{
         return $this->_validator ;
	}	
	public function setValidator($_validator)
	{   
         $this->_validator = $_validator ;
	}
		
		
	public function getHandler()
	{
         return $this->_save_handler ;
	}	
	public function setHandler($_handler)
	{   
         $this->_save_handler = $_handler ;
	}
	
	static protected function createSessionDirectory($config)
    {
        if(! isset($config['save_path']))    {
            return false ;
        }
        $path = $config['save_path'] ;
        ##
        if(! is_dir($path))    {
            ##
            mkdir($path , 0760 , true) ;
        }
    }
}