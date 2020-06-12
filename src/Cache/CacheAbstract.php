<?php
namespace DV\Cache ;

use Laminas\Cache\StorageFactory;
use Laminas\Cache\Storage\Adapter ;
/**
 * Laminas_Cache Engine on Darrism Solution specification
 * 
 * @author Darrism Solution Ltd
 * @copyright 2012|2015
 * @version 2.0.0
 *
 */
abstract class CacheAbstract
{
	/**
	 * Laminas\Cache\StorageFactory Engine
	 * 
	 * @var \Laminas\Cache\StorageFactory
	 */
	protected static $_cache = null ;

    protected static $options = null ;

	
	/**
	 * default Options for the Cache Adapter
	 * 
	 * @var array
	 */
	public static $_adapterOptions = [
				'ttl' => 7200, ## cache lifetime of 2 hours
				'namespace' => PROJECT_NAME , 
	];
	
	
	/**
	 * Default plugin options that will be called
	 * 
	 * @var unknown_type
	 */
	public static $plugins = [
				'exception_handler' => [
						'throw_exceptions' => false
				],
	];


	/**
	 * initiate the cache engine
	 * @param array $_options
	 *
	 */
	public function __construct($_options=[])
	{	
		### set the options
		$this->setOptions($_options) ;

		if(null === self::$_cache)	{
			self::$_cache = StorageFactory::factory($this->getOptions()) ;				
		}		
	}
	
		
	protected function getOptions()
	{
		return (array) self::$options ;
	}
	
	
	protected function setOptions($_options)
	{
		self::$options = $_options ;
	}
		
}