<?php
namespace DV\Engine\VolumeLicensing ;


use Zend\EventManager\AbstractListenerAggregate ;
use Zend\EventManager\EventManagerInterface ;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\Mvc\MvcEvent ;
use DV\Mvc\Service\ServiceLocatorFactory ;
use DV\Service\GotoUrl;
use DV\Service\ActionControl as Veiw_Service_ActionControl ;
use DV\Cache\Storage\Adapter\Sqlite\Sqlite3;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;
use Zend\Log\Formatter\Simple;
use Zend\Config\Config;
use DV\System\Cpu;
use Zend\Config\Writer\Ini;
use Zend\Config\Reader\Ini as reader_ini ;
use DV\Service\FlashMessenger;
use DirectoryIterator ;
use DV\Cache\Storage\Plugin\Crypt;
use Doctrine\Common\Collections\ArrayCollection;
use Zend\Mvc\Exception\RuntimeException as mvcException ;


class DV extends AbstractListenerAggregate
{
    use ListenerAggregateTrait ;

	protected $_model;
	
	protected  $_log;
	
	const CLIENT_LIC_ID = 'eftlic' ;
	
	const CLIENT_CORE_ID = 'eftcore' ;
	
	const CORE = 'core'; 
	
	const LIC = 'lic' ;
	
	const KEY_DEFINITION_0110 = '0110' ;
	
	const KEY_DEFINITION_0111 = '0111' ;
	
	const KEY_DEFINITION_1111 = '1111' ;
	
	const APP_PHP_VERSION = '7.0.0' ;
	
	protected static $_lic_ext = ['zip'] ;
	
	
	public static function installer(MvcEvent $event)
	{
		### installer file directory
		$installer_file = realpath(APPLICATION_PATH . '/../data/install/installer.ini') ;
		
		### fetch the installer file
		if(! file_exists($installer_file))	{
			throw new mvcException('unable to locate/corrupted installer file. The installer file is used in knowing the installation 
									status of the application. if the problem persist, contact the support') ;
		}
		
		###
		$ini_config = new reader_ini($installer_file) ;
		###
		$installer_config = $ini_config->fromFile($installer_file) ;
		###
		$installer_config = new Config($installer_config , true) ;
		
		###
		$eventManager = $event->getApplication()->getEventManager() ;
		###
		$routematch = $event->getRouteMatch() ;
		### installer controller string
		$installer_controller = 'Install\Controller\Installer' ;
			
		### check for result section of the installer file
		if(! $installer_config->offsetExists('result'))	{			
			### if the requesting controller is not installer controller
			if($routematch->getParam('controller') != $installer_controller)	{
				### for a successful routematch redirect, the operation must happen in MVCEvent::Dispatch event with highest priority
				$routematch->setParam('controller' , $installer_controller) ;
				$routematch->setParam('action' , 'index') ;
			}			
		}
		else{
			### incase result has been created but the installation hasn't complete
			$installer_result_progress = $installer_config->progress ;
			
			if(! $installer_result_progress instanceof \Zend\Config\Config)	{
				###
				throw new mvcException('unable to load the progress of installation due to error installation progress report') ;				
			}
			
			foreach($installer_result_progress as $progress_step)	{
				###
				foreach ($progress_step as $progress)	{
					### check if any of the progress is still zero
					if($progress <= 0)	{
						### redirect back to installer like when the result has not been created
						### if the requesting controller is not installer controller
						if($routematch->getParam('controller') != $installer_controller)	{
							### for a successful routematch redirect, the operation must happen in MVCEvent::Dispatch event with highest priority
							$routematch->setParam('controller' , $installer_controller) ;
							$routematch->setParam('action' , 'index') ;
						}
					}
				}
			}
			
			### if the installation result are available, then load the rest of the event
			#$eventManager->attach(new self()) ;
		}
	}
	
	
	public function postInstallerOperations(MvcEvent $event)
	{	
	    ### don't load $this for Install Module
	    $routeMatch = $event->getRouteMatch() ;
	    ###
	    $controller_n_module = $routeMatch->getParam('controller') ;
	    ### installer controller string
	    $installer_controller = 'Install\Controller\Installer' ;
	    /* $client = self::get_client() ;
	    
	    ### fetch the system client table to know if this is fresh installation
	    if(null == count($client))    {
	     	$freshInstall = true ;
	    }
	    else{
		    ### incase user information has been insert
		    if(self::get_client()->getInstall() <= Veiw_Service_ActionControl::ZERO)    {
				$freshInstall = true ;    	
		    }
	    } */
	    
	    if(strpos($controller_n_module, '\\') !== false)	{
	    	###
	    	list($module, $controller_string, $controller) = explode('\\' , $controller_n_module) ;
	    	
	    	###
		    if(in_array($module , ['Install' , 'VL']))	{		    	
		    	##
		    	return ;
		    }
		    
		    /* ### check if it is a fresh installation
		    if(isset($freshInstall))	{
		    	###
		    	if((strtolower($controller) != 'troubleshoot') || ($routeMatch->getParam('action') != 'index'))	{		    
			    	## redirect user to
			    	#$url = GotoUrl::assemble(['controller' => 'troubleshoot' , 'action' => 'index'], 'installer') ;
			    	$routeMatch->setParam('controller', $installer_controller) ;
			    	$routeMatch->setParam('action', 'index') ;
		    		return $routeMatch ;
		    	}
		    } */
	    }
	    
	    ### load the core engine config
	    $_core_engine =  self::get_core_engine();
	    
	    ### load the core engine first
	    if(! $core_config = $_core_engine->getItem(self::CLIENT_CORE_ID))    {
	    	### if the core engine cannot load, then regenerate a new once that will be loaded
	    	### create the core file string
	    	$core_file = self::inititate_core_file() ;
	    	### load the ini config file
	    	$core_ini_reader = new reader_ini() ;
	    	### read the core ini data from file
	    	$core_ini_read_data  = $core_ini_reader->fromFile($core_file) ;
	    	
	    	### save the core config
	    	$_core_engine->setItem(self::CLIENT_CORE_ID , new Config($core_ini_read_data)) ;
	    	### load the cache again
	    	$core_config = $_core_engine->getItem(self::CLIENT_CORE_ID) ;
	    } 
	    
	    ### we believe that core config should no longer  be empty at dis point
	    if(null == $core_config)	{
	    	throw new \Exception('unable to load the system runtime configuration for license validation');
	    }
	   		
	    ### load the lic engine config
		$_lic_engine = self::get_lic_engine() ;	
		
		### check for cache miss
		if(! $lic_config = $_lic_engine->getItem(self::CLIENT_LIC_ID))    {
			###
			$url = GotoUrl::assemble(['controller' => 'troubleshoot' , 'action' => 'invalid-license-file'], 'installer') ;
			
		    ### try to autoload lic file from default directory
		    if(! self::autoload_lic_file())    {
				### clear the core cache file automatically so that it can be reloaded, until lic_config loading is valid
				$_core_engine->flush() ;	
				###
				$url .= '?reason=autoload-license-file-failed';				
	        	###
	       	 	return GotoUrl::redirector($url) ;
		    }
		    
		    ### reload the lic engine once again after the lic engine autoload logic has finish
		    if(! $lic_config = $_lic_engine->getItem(self::CLIENT_LIC_ID))    {
				### clear the core cache file automatically so that it can be reloaded, until lic_config loading is valid
				$_core_engine->flush() ;	
				###
				$url .= '?reason=second-attempt-to-autoload-license-file-failed';
	        	###
	       	 	return GotoUrl::redirector($url) ;
		    }		    
		}
		
		###
		$url = GotoUrl::assemble(['controller' => 'troubleshoot' , 'action' => 'invalid-license-file'], 'installer') ;
		/**
		 * do a logic that will do property to property comparism for core & lic
		 */
		### fetch the same interaction reference from lic engine
		$lic_engine_core = $core_config->core ;
		
		$core_engine_core = $lic_config->core ;		
		
		if(null == $lic_engine_core)	{ 
			$url .= '?reason=license-engine-initialization-failed';
			return GotoUrl::redirector($url) ;
		}
		
		if($lic_engine_core != $core_engine_core)	{
			$url .= '?reason=core-n-license-file-incompatible';
			return GotoUrl::redirector($url) ;
		}
		
		foreach ($core_engine_core as $server_key => $core_server)    {		    
		     
		     foreach($core_server as $proc_bios_os_key => $core_proc_bios_os)    {
		          
		         foreach ($core_proc_bios_os as $proc_bios_os_info_key => $_core_proc_bios_os_info)    {    		    
		             
        		    ### check for server key in lic engine configuration
        		    if(! count($lic_engine_core->get($server_key)))    { 
        		    	 $url .= '?reason=license-file-server-key-failed';
        		         return GotoUrl::redirector($url) ;
        		    }
        		    ### fetch the instance of server key from lic engine
        		    $_lic_proc_bios_os_info = $lic_engine_core->{$server_key} ;        		    
        		    
        		    ### check for server->processor | bios | os in the lic engine config
        		    if(! count($_lic_proc_bios_os_info->get($proc_bios_os_key)))    { 
        		    	$url .= '?reason=license-engine-os-key-failed';
        		        return GotoUrl::redirector($url) ;
        		    }
        		    ### fetch the instance of server->processor | bios | os key from lic engine
        		    $_lic_proc_bios_os_info = $_lic_proc_bios_os_info->{$proc_bios_os_key} ;        		    
        		    
        		    ### check for server->processor | bios | os -> info in the lic engine config
        		    if(! count($_lic_proc_bios_os_info->get($proc_bios_os_info_key)))    { 
        		    	 $url .= '?reason=license-engine-os-information-key-failed';
        		         return GotoUrl::redirector($url) ;
        		    }
		             		             
		            ### load the server->proc/bios/os->info property of lic engine as much as the core engine load its own
		            #$_lic_proc_bios_os_info = $_lic_proc_bios_os_info->{$proc_bios_os_info_key} ;.
		            $_lic_proc_bios_os_info = $lic_engine_core->{$server_key}->{$proc_bios_os_key}->{$proc_bios_os_info_key} ;
		             
		            if($_lic_proc_bios_os_info !== $_core_proc_bios_os_info)    {
		            	$url .= '?reason=license-n-core-os-info-icompactible';
		                return GotoUrl::redirector($url) ;
		            }
		         }
		     }
		}		
		
		/**
		 * The logic here will update the client database with necessary information
		 * 
		 */
		if(self::get_client()->getInstall() <= Veiw_Service_ActionControl::ONE)    {
			###
			$system_model = new \DV\Model\Save\System() ;
			### prepare some parameter to pass
			$lic_data['client-entity-row'] = self::get_client() ;
			### call the confirm lic installation method
			if(! $system_model->confirm_lic_installation($lic_data))	{
				throw new \Exception('unable to confirmation installation of license file') ;
			}
		}
		
		### fetch the expiry date from the config file
		$exp_date_string = $lic_config->key->registered->expiry->date ;
		$start_date_string = $lic_config->key->registered->start->date ;
		$core_proc_id = $lic_config->core->server->processor->id ;
		$key = self::get_key() ;
		
		### fetch the key definition
		$key_definition = $lic_config->key->registered->definition ;
	
		### create the expiry date object
		$exp_date = new \DateTime($exp_date_string) ;
		$start_date = new \DateTime($start_date_string) ;
		$today = new \DateTime ;
		
		$key_in_pieces = explode(Veiw_Service_ActionControl::DASH , $key) ;		
		$compose_definition = md5($key_in_pieces[1] . $core_proc_id) ;
		
		### check for demo version.
		if($compose_definition == $key_definition)    {
		    FlashMessenger::message('0215');
		}
		
		### check for trial
		if($compose_definition == $key_definition)    {
		    FlashMessenger::message('0214');
		}
		
		if($exp_date == $today)    {
		    FlashMessenger::message('0217');
		}
		
		### using the negative mode to compare the date
		if($today < $start_date)    {		   
		    ### clear the core cache file automatically so that it can be reloaded, until lic_config loading is valid
		    $_lic_engine->flush() ;
		    FlashMessenger::message('error' , 'Your license has not reach the configured start date');
		    $url .= '?reason=license-start-date-not-ready';
		    return GotoUrl::redirector($url) ;
		    exit ;
		}
		
		### using the negative mode to compare the date
		if($today > $exp_date)    {		   
		    ### clear the core cache file automatically so that it can be reloaded, until lic_config loading is valid
		    $_lic_engine->flush() ;
		    FlashMessenger::message('error' , 'Your license has expired, please contact support');
		    $url .= '?reason=license-has-expired';
		    return GotoUrl::redirector($url) ;
		    exit ;
		}
		
	}
	
	/**
	 * Fetch for the License Model.
	 * 
	 * @return \DV\Cache\Storage\Adapter\Sqlite\Sqlite3
	 */
	static public function get_lic_engine()
	{
		### fetch the service manager
		$sm = self::serviceManager() ;
		### 
		$_options['adapter']['ttl'] = 0;
    	$_options['adapter']['namespace'] = 'eftlic';
    	$_options['adapter']['namespace_separator'] = '_';
    	$_options['adapter']['pathname'] = APPLICATION_PATH . '/../data/veiw/darrism---eftliceftlic.db';
    	$_options['adapter']['automaticVacuumFactor'] = 1;
    				
    	$_lic_engine = new \DV\Cache\Storage\Adapter\Sqlite\Sqlite3() ;
    	### set the adapter Options
    	$_lic_engine->setOptions($_options['adapter']) ;
    	
    	$serialize_plugin = new \Zend\Cache\Storage\Plugin\Serializer();
    	### add the plugin
    	$_lic_engine->addPlugin($serialize_plugin);
    						
    	$exception_plugin = new \Zend\Cache\Storage\Plugin\ExceptionHandler() ;
    	$exception_plugin->getOptions()->setThrowExceptions(true) ;
    	### add the exception plugin
    	$_lic_engine->addPlugin($exception_plugin) ; 
    	
		return $_lic_engine ;
	}
	
	/**
	 * Fetch for the License Model.
	 * 
	 * @return \DV\Cache\Storage\Adapter\Sqlite\Sqlite3
	 */
	static public function get_core_engine()
	{	
		### fetch the service manager
		$sm = self::serviceManager() ;
		###
		$_options['adapter']['ttl'] = 86400;
		$_options['adapter']['namespace'] = 'eftcore';
		$_options['adapter']['namespace_separator'] = '_';
		$_options['adapter']['pathname'] = APPLICATION_PATH . '/../data/core/darrism---eftcoreeftcore.db';
		$_options['adapter']['automaticVacuumFactor'] = 1;
			
	    ### check if the file is available to avoid zend_cache error
	    if(! file_exists($_options['adapter']['pathname']))    {
	        
	    }
	   
	   ### initiating the cache engine
	   $_core_engine = new \DV\Cache\Storage\Adapter\Sqlite\Sqlite3() ;
	   ### set the adapter Options
	   $_core_engine->setOptions($_options['adapter']) ;
	   
	   $serialize_plugin = new Crypt();
	   ### add the plugin
	   $_core_engine->addPlugin($serialize_plugin);
	   
	   $exception_plugin = new \Zend\Cache\Storage\Plugin\ExceptionHandler() ;
	   $exception_plugin->getOptions()->setThrowExceptions(true) ;
	   ### add the exception plugin
	   $_core_engine->addPlugin($exception_plugin) ;	   
	    
	   return $_core_engine ;
	}
	
	
	static public function _inititate_core_file()
	{
		return self::inititate_core_file();
	}
	static private function inititate_core_file()
	{	    
	    ### create the system file string
	    $core_file = APPLICATION_PATH .'/../data/install/core.ini' ;
	    
	    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')     {
	    	
    	    ### check for the CPU file
    	    if(file_exists($core_file))    {
    	       ### alwaz delete the exiting core config once this method is initiate
    	       chmod($core_file, 0777) ;
    	       unlink($core_file) ; 
    	    }
    	    
    	    ### initiate a config engine
    	    $config = new Config([] , true) ;
    	    $config->core = [] ;
    	        
    	    ### inititate the core class
    	    $core_class = new Cpu() ;
    	    ### initiate the WMI command
    	    $core_class_wmi = $core_class->wmi() ;
    	        
    	    ### run the BIOS Pool Query
    	    $wmi_proc_pool  =  $core_class_wmi->ExecQuery("Select * from Win32_Processor") ;
    	    ### start adding the necessary configuration
    	    $config->core->server = [];
    	    ### create the server processor key
        	$config->core->server->processor = [] ;	        
    	   ### iterate through the processor info
    	   foreach($wmi_proc_pool as $proc_info)    {
                    ### populate the processor info
        	        $config->core->server->processor->manufacturer = $proc_info->Manufacturer ;
        	        $config->core->server->processor->id = $proc_info->ProcessorId ;
        	        $config->core->server->processor->uniqueid = $proc_info->UniqueId ;
        	        $config->core->server->processor->name = $proc_info->Name ;
        	        $config->core->server->processor->cores = $proc_info->NumberOfCores ;
        	        $config->core->server->processor->family = $proc_info->Family ;
        	        $config->core->server->processor->architecture = $proc_info->Architecture ;
        	        $config->core->server->processor->description = $proc_info->Description ;
    	   }
    	     
    	   ### run the BIOS Pool Query
    	   $wmi_bios_pool  =  $core_class_wmi->ExecQuery("Select * from Win32_BIOS") ;
    	   ### create the server bios tree
    	   $config->core->server->bios = [] ;	        
    	   ### iterate the bios pool
    	   foreach ($wmi_bios_pool as $bios_info)    {
    	        ### populate the bios info
    	        $config->core->server->bios->manufacturer = $bios_info->Manufacturer ;
    	        $config->core->server->bios->serial = $bios_info->SerialNumber ;
    	        $config->core->server->bios->description = $bios_info->Description ;
    	        $config->core->server->bios->version = $bios_info->Version ;
    	   }
    
    	   ### run the BIOS Pool Query
    	   $wmi_system_os  =  $core_class_wmi->ExecQuery("Select * from Win32_OperatingSystem") ;
    	   ### create the OS key tree
    	   $config->core->server->os = [] ;
    	   ### iterate the system core os
    	   foreach($wmi_system_os as $server_os)    {	            
                    ###
                    $config->core->server->os->name = $server_os->Name ;
                    $config->core->server->os->organisation = $server_os->Organization ;
                    $config->core->server->os->architecture = $server_os->OSArchitecture ;
                    $config->core->server->os->producttype = $server_os->ProductType ;
                    $config->core->server->os->serialnumber = $server_os->SerialNumber ;
    	   }
          
    	   ### write the configuration to file
           $writer = new Ini();
           $writer->toFile($core_file , $config) ;
	    }
	    
	    return $core_file ;
	}
	
	/**
	 * autoload license file from root/efiletrack folder
	 */
	static private function autoload_lic_file()
	{
	    $root_dir = realpath(getenv('SystemDrive').'/efiletrack') ;
	   
	    ### check for the existence of the root dir
	    if(! is_dir($root_dir))    {
	        return false ;
	    }
	    
	    ### inititate a root directory iterator
	    $root_dir_iterator = new DirectoryIterator($root_dir) ;
	    ### iterate through the root directory
	    foreach($root_dir_iterator as $root_dir_file)    {
	        if(! $root_dir_file->isDot() &&
	                    in_array($root_dir_file->getExtension() , self::$_lic_ext))    { 
	            ### initiate a new zip archive
	            $archive = new ZipArchive() ;
	            ### fetch the zip filename
	            $lic_zip_filename = $root_dir_file->getPathname() ;
	            ### open the zip file && verify opening
	            if($archive->open($lic_zip_filename))    {
	                ### lic_file_dir for eft
	                $_lic_file_dir = realpath(APPLICATION_PATH . '/../data/veiw') ;
	                ### extract the lic file
	                if(! $archive->extractTo($_lic_file_dir))    {
	                	return false ;
	                }
	                
	                $archive->close() ;
	                
	                ### automatically remove the lic zip file
	                if(file_exists($lic_zip_filename))    {
	                	### alwaz delete the exiting core config once this method is initiate
	                	chmod($lic_zip_filename , 0777) ;
	                	unlink($lic_zip_filename) ;
	                }
	            }
	            else{
	                return false ;
	            }
	        
	            ### break the iteration after lic_zip file extraction
	            break ;	           
	            
	        }

	    }
	}

	/**
	 * check for the installed PHP version
	 * @param EventManagerInterface $event
	 */
	static public function check_php_version(MvcEvent $event)
	{
	    if (version_compare(PHP_VERSION, self::APP_PHP_VERSION) == '-1') {
	        ### redirect to install
	        /* $url = GotoUrl::assemble(['controller' => 'index' , 'action' => 'invalid-php-version'], 'installer') ;
	        ###
	        return GotoUrl::redirector($url) ; */
	    	printf('invalid PHP version. The application can only run on <= %s' , self::APP_PHP_VERSION) ;
	    	$event->trigger(MvcEvent::EVENT_DISPATCH_ERROR) ;
	    }
	}
	
	static public function load_lic_meta()
	{
	    $lic_engine = self::get_lic_engine() ;
	    
	    if(! $load_lic_engine = $lic_engine->getItem(self::CLIENT_LIC_ID))    {
	        throw new \Exception('Unable to load license engine') ;
	    }
	    return $load_lic_engine ;
	}
	
	
	static public function get_key()
	{
		return (null != self::load_lic_meta()) ? self::load_lic_meta()->key->registered->key : null ;
	}
	
	
	static public function getNoOfUser()
	{
		$key = self::get_key() ;
		
		if(null == $key)    {
		    return false ;
		}
	
		### break the key
		$key_in_pieces = explode(Veiw_Service_ActionControl::DASH , $key) ;
	
		$no_of_user = $key_in_pieces[2] ;
	
		$flip_reserve_key = (int) substr(strrev($no_of_user), 1) ;
	
		return $flip_reserve_key ;
	}
	
	
	public function reset_engine(Sqlite3 $engine , $mode=Sqlite3::CLEANING_MODE_ALL)
	{
		return $engine->clean($mode) ;
	}
	
    /**
     * Log the occured error into a file.
     */
    public function log()
    {
    	if (null === $this->_log) {
    		### writer
    		$writer = new Stream(APPLICATION_PATH . '/data/logs/nnecms-client.log');
    		### Formatter	
    		$formatter = new Simple('%timestamp% :: %message%' . PHP_EOL);
    		$writer->setFormatter($formatter);
    			
    		$log = new Logger();
    		$log->addWriter($writer);
    			
    		$this->_log = $log;    			
  		} 
           
        return $this->_log;
    }
    
    
    /**
     * Fetch Client information from DB
     * 
     * @throws \Exception
     * @return \DV\Entity\TblEfiletrackClient
     */
    static public function get_client()
    {
        $system_model = new \DV\Model\Save\System() ;
       
        $client = $system_model->getSystemSettings(['rowset' => ['activated' => Veiw_Service_ActionControl::YES] ,     
        											'repository' => 'TblSystemClient'
        ]) ;
        
        ### make sure only one client record is available
        if(null != count($client) && count($client) >= Veiw_Service_ActionControl::TWO)    {
        	### called the user saved model
        	$user_model = new \DV\Model\Save\User() ;
            ### delete the rest of the record and leave only one client
            $user_model->delete_excess_client($client) ;
        }        
        
        if($client instanceof ArrayCollection)	{
        	$client->current() ;
        }
        elseif (is_array($client))		{
        	$client = current($client); 
        }

        return $client ;
    }
    

    
    /**
     * @return \Zend\ServiceManager\ServiceManager
     */
    static private function serviceManager()
    {
    	return ServiceLocatorFactory::getInstance() ;
    }
    
    
    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events , $priority=1)
    {
    	$this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE , [$this , 'check_php_version'] , 100000);
    	
    	#$this->listeners[] = $events->attach('__INITITATE_CORE_FILE__' , [$this , '_inititate_core_file']);
    	# handle database configuration
    	#$this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH , [$this , 'installer'] , 10000);
    	
    	#$this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH , [$this , 'postInstallerOperations'] , 9999);
    	/* $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR , function ()	{
    		### call the log engine
    		$log_engine = $this->log() ;
    		### log the error
    		$log_engine->err('I need to know how to load error message from EVENT_DISPATCH_ERROR EM') ;
    		
    	} , -100000);
    	$this->listeners[] = $events->attach(MvcEvent::EVENT_RENDER , function() {
    		###
    		$sm = self::serviceManager() ;
    		###
    		$verifier = $sm->get('DV\Validator\VerifyLic') ;
    		###
    		if(! $verifier->isValid('justAnyThing'))	{
    			#$events->trigger(MvcEvent::EVENT_FINISH)->stopped() ;
    		}
    	} , 100000); */
    }
}
