<?php
namespace DV\Cache ;

use Laminas\Cache\StorageFactory;


class Engine extends CacheAbstract
{
	
	public static $_instance = null ;
		
	
	public function __construct($_options=[])
	{
		parent::__construct($_options);
	}		
		
	/**
	 * 
	 * @param array $_options
	 * @return \Laminas\Cache\StorageFactory
	 */
	public static function getInstance($_options=[])
	{
		if (null === self::$_instance) {
	         self::$_instance = new self($_options) ;
	    }
	
	    return self::$_cache ;	        
	}

	
	/**
	 * 
	 * @param string $adapterName
	 * @return \Laminas\Cache\StorageFactory
	 */
	public static function adapterFactory($adapterName)
	{
		if(null === self::$_cache)	{
            $self = new self();
			self::$_cache = StorageFactory::adapterFactory($adapterName , $self->getOptions()) ;
		}
			
		return self::$_cache ;
	}
	
}