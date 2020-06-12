<?php

namespace DV\Session ;

use Laminas\Session\SessionManager as SNManager ;
use Laminas\Session\Container ;


class Session 
{
	/**
	 * @var \Laminas\Session\SessionManager
	 */
	protected $_sessionManager;
	
	protected $_items;
	
	/** 
	 * @var \Laminas\Session\Container
	 */
	protected $_container ;
	
	/**
	 * @var string
	 */
	const NS = 'VEIW' ;
	
	/**
	 * @var array
	 */
	public static $_default_option_setting = array(
					'useDefaultContainer' => true ,
					'startSession' => true
	) ;


	/**
	 * Constructor
	 *
	 * @param null $_options
	 * @throws \Exception
	 * @internal param Config\ConfigInterface|null $config
	 * @internal param Storage\StorageInterface|null $storage
	 * @internal param SaveHandler\SaveHandlerInterface|null $saveHandler
	 */
	public function __construct($_options=null)
	{
		$c_session_config = new \DV\Session\Config() ;
		### fetch the session config object
		$session_config_object = $c_session_config->getConfig() ;
		
		###
		if(null == $this->_sessionManager)	{
			$sessionManager = new SNManager($session_config_object , $c_session_config->getStorage()) ;				
		}
		
		### fetch validators if only if they are available
		if (null != $c_session_config->getValidator()) 	{ 
			### fetch the session manager session validator chain
			$chain = $sessionManager->getValidatorChain() ;
				
			### iterate through the set validator and
			foreach ($c_session_config->getValidator() as $validator) {
			### instantiate through the validators
				$validator = new $validator() ; 
				### attache the validators to the Session manager validator chain
				$chain->attach('session.validate', array($validator, 'isValid')) ;
			}
		}
		
		
		### check and fetch save handler
		if(null != $c_session_config->getHandler())	{
			$sessionManager->setSaveHandler($c_session_config->getHandler()) ;
		}
				
		### set the session namespace manager
		$this->setSessionManager($sessionManager) ;
		
		### check if any other options is set and extract the variables
		if(null == $_options)	{
			$_options = self::$_default_option_setting ;
		}
		### extract the key as variables
		extract($_options) ;
		
		### check if the Default Container is set.
		if(isset($useDefaultContainer))	{
			### set the container 
			$this->setDefaultContainerManager() ;
		}
		
		/* $php_tmp_dir = ini_get('session.save_path') ;
		chmod($php_tmp_dir , '0777') ; */
		### check if the session is to be started automatically
		if(isset($startSession))	{
			### set the container 
			$this->getSessionManager()->start() ;
		}
		 
	}


	/**
	 * Set the Session Manager
	 * @param SNManager $sManager
	 * @return $this
	 */
	
	public function setSessionManager(SNManager $sManager)
	{
		$this->_sessionManager = $sManager ;
		
		return $this ;
	}
	
	/**
	 * Fetch the Instantiated Session Manager
	 * 
	 * @return \Laminas\Session\SessionManager
	 */
	public function getSessionManager()
	{		
		return $this->_sessionManager;
	}	
	
	
	/**
	 * Set the container manager
	 * 
	 * @return \Laminas\Session\Container::setDefaultManager()
	 */
	public function setDefaultContainerManager()
	{
		return Container::setDefaultManager($this->getSessionManager()) ;
	}
	
	/**
	 * implement a means of centralizing session container.
	 * 
	 * @param string $namespace
	 * @return \Laminas\Session\Container
	 */
	public function getContainer($namespace=null)
	{	
		if(null == $this->_container)	{
			### check if a namespace is provided
			if(null == $namespace)	{
				$namespace = PROJECT_NAME ;
			}
			
			$this->setContainer(new Container($namespace)) ;
		}	
		return $this->_container ;
	}
	public function setContainer(Container $container)
	{		
		$this->_container =  $container;
		return $this ;
	}
	
	
	public function isExist($key) 
	{
		#return ($this->getSessionManager()->getStorage()->offsetExists(self::NS) &&	! is_null($this->getStorage()));
		return ($this->getContainer()->offsetExists($key));
	}
	
	
	public function clear($key=null) 
	{
		return $this->getContainer()->getManager()->getStorage()->clear($key);
	}
	
	/**
	 * @param string $key
	 * @param string $value
	 */
	public function setData($key, $value) 
	{
		$this->getContainer()->offsetSet($key, $value) ;
	}
	
	/**
	 * @param string $key
	 * @return string
	 * @throws \Exception
	 */
	public function getData($key) 
	{
		return $this->getContainer()->offsetGet($key) ;
	}

	/**
	 * @param $namespace
	 * @return string
	 * @internal param string $key
	 */
	public function hasContainer($namespace) 
	{
		$data = $this->getStorage() ;
	
		return isset($data[$namespace]) ;
	}
	
	
	/**
	 * @param array $data
	 
	public function setData($data) 
	{
		$namespace = self::NS;
		$this->getContainer()->getManager()->getStorage()->$namespace = $data;
	}
	*/
	
	/**
	 * Fetch the session storage 
	 * 
	 * @return \Laminas\Session\ManagerInterface::getStorage()
	 */
	public function getStorage() 
	{
		$storage = $this->getContainer()->getManager()->getStorage() ;
		
		return $storage ;
	}
	
	
	/* public function __invoke()
	{
		return $this->_container ;
	} */
	
}