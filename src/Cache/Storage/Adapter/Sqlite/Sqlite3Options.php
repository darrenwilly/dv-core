<?php
namespace DV\Cache\Storage\Adapter\Sqlite;

use Laminas\Cache\Storage\Adapter\AdapterOptions ;
/**
 * These are options specific to the SQLite adapter
 */
class Sqlite3Options extends AdapterOptions
{
    /**
     * Namespace separator
     *
     * @var string
     */
    protected $namespaceSeparator = ':';

    /**
     * Pathname to the database file
     *
     * @var string
     */
    protected $pathname = '';

   
    /**
     * The name of the handler which shall be used for accessing the database.
     *
     * @var string
     */
    protected $automaticVacuumFactor ;

    /**
     * Set namespace separator
     *
     * @param  string $namespaceSeparator
     * @return DbaOptions
     */
    public function setNamespaceSeparator($namespaceSeparator)
    {
        $namespaceSeparator = (string) $namespaceSeparator;
        $this->triggerOptionEvent('namespace_separator', $namespaceSeparator);
        $this->namespaceSeparator = $namespaceSeparator;
        return $this;
    }

    /**
     * Get namespace separator
     *
     * @return string
     */
    public function getNamespaceSeparator()
    {
        return $this->namespaceSeparator;
    }

    /**
     * Set pathname to database file
     *
     * @param string $pathname
     * @return DbaOptions
     */
    public function setPathname($pathname)
    {
    	if(! file_exists($pathname))	{
    		### check may be the directory to the file is valid, then that means only the file does not exist    		
    		if(is_dir(dirname($pathname)))	{
    			### if the directory exist and file does not, then create the file instead
    			fopen($pathname , 'w+') ;
    		}else{
    			throw new \Exception('the directory/file does not exist') ;
    		}
    	}
    	
        $this->pathname = (string) $pathname;
        $this->triggerOptionEvent('pathname', $pathname);
        return $this;
    }

    /**
     * Get pathname to database file
     *
     * @return string
     */
    public function getPathname()
    {
        return $this->pathname;
    }


    public function setAutomaticVacuumFactor($automaticVacuumFactor)
    { 	
    	$this->automaticVacuumFactor = $automaticVacuumFactor;
        $this->triggerOptionEvent('automaticVacuumFactor', $automaticVacuumFactor);
        return $this;
    }

    public function getAutomaticVacuumFactor()
    {
        return $this->automaticVacuumFactor;
    }
}
