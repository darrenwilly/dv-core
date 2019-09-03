<?php
namespace DV\Cache\Storage\Adapter\Sqlite;

use Zend\Cache\Storage\Adapter\AbstractAdapter ;
use stdClass;
use Traversable;
use Zend\Cache\Exception;
use Zend\Cache\Storage\AvailableSpaceCapableInterface;
use Zend\Cache\Storage\Capabilities;
use Zend\Cache\Storage\FlushableInterface;
use Zend\Cache\Storage\IterableInterface;
use Zend\Cache\Storage\OptimizableInterface;
use Zend\Cache\Storage\TotalSpaceCapableInterface;
use Zend\Stdlib\ErrorHandler;

class Sqlite3 extends AbstractAdapter implements
    AvailableSpaceCapableInterface,
    FlushableInterface,
    OptimizableInterface,
    TotalSpaceCapableInterface
{
	
	const CLEANING_MODE_ALL = 'all' ;
	
	const CLEANING_MODE_OLD = 'all' ;
    /**
     * DB ressource
     *
     * @var mixed $_db
     */
    private $_db = null;

    /**
     * Boolean to store if the structure has benn checked or not
     *
     * @var boolean $_structureChecked
     */
    private $_structureChecked = false;

    /**
     * Buffered total space in bytes
     *
     * @var null|int|float
     */
    protected $totalSpace;

    /**
     * Constructor
     *
     * @param  null|array|Traversable|DbaOptions $options
     * @throws Exception\ExceptionInterface
     */
    public function __construct($options = null)
    {
        if (! extension_loaded('pdo_sqlite')) {
            throw new Exception\ExtensionNotLoadedException('Cannot use SQLite3 storage because the "pdo_sqlite" extension is not loaded in the current PHP environment');
        }

        parent::__construct($options);
        
       # $this->_getConnection();
    }

    /**
     * Destructor
     *
     * Closes an open dba resource
     *
     * @see AbstractAdapter::__destruct()
     * @return void
     */
    public function __destruct()
    {
        $this->_db = null;

        parent::__destruct();
    }

    /* options */

    /**
     * Set options.
     *
     * @param  array|Traversable|Sqlite3Options $options
     * @return self
     * @see    getOptions()
     */
    public function setOptions($options)
    {
        if (! $options instanceof Sqlite3Options) {
            $options = new Sqlite3Options($options);
        }

        return parent::setOptions($options);
    }

    /**
     * Get options.
     *
     * @return DbaOptions
     * @see    setOptions()
     */
    public function getOptions()
    {
        if (! $this->options) {
            $this->setOptions(new Sqlite3Options());
        }
        return $this->options;
    }

    /* TotalSpaceCapableInterface */

    /**
     * Get total space in bytes
     *
     * @return int|float
     */
    public function getTotalSpace()
    {
        if ($this->totalSpace === null) {
            $pathname = $this->getOptions()->getPathname();

            if ($pathname === '') {
                throw new Exception\LogicException('No pathname to database file');
            }

            ErrorHandler::start();
            $total = disk_total_space(dirname($pathname));
            $error = ErrorHandler::stop();
            if ($total === false) {
                throw new Exception\RuntimeException("Can't detect total space of '{$pathname}'", 0, $error);
            }
            $this->totalSpace = $total;

            // clean total space buffer on change pathname
            $events     = $this->getEventManager();
            $handle     = null;
            $totalSpace = & $this->totalSpace;
            $callback   = function ($event) use (& $events, & $handle, & $totalSpace) {
                $params = $event->getParams();
                if (isset($params['pathname'])) {
                    $totalSpace = null;
                    $events->detach($handle);
                }
            };
            $events->attach('option', $callback);
        }

        return $this->totalSpace;
    }

    /* AvailableSpaceCapableInterface */

    /**
     * Get available space in bytes
     *
     * @return float
     */
    public function getAvailableSpace()
    {
        $pathname = $this->getOptions()->getPathname();

        if ($pathname === '') {
            throw new Exception\LogicException('No pathname to database file');
        }

        ErrorHandler::start();
        $avail = disk_free_space(dirname($pathname));
        $error = ErrorHandler::stop();
        if ($avail === false) {
            throw new Exception\RuntimeException("Can't detect free space of '{$pathname}'", 0, $error);
        }

        return $avail;
    }

    /* FlushableInterface */

    /**
     * Flush the whole storage
     *
     * @return bool
     */
    public function flush()
    {
        $pathname = $this->getOptions()->getPathname();

        if ($pathname === '') {
            throw new Exception\LogicException('No pathname to database file');
        }

        if (file_exists($pathname)) {
            // close the dba file before delete
            // and reopen (create) on next use
            $this->_close();

            ErrorHandler::start();
            $result = unlink($pathname);
            $error  = ErrorHandler::stop();
            if (! $result) {
                throw new Exception\RuntimeException("unlink('{$pathname}') failed", 0, $error);
            }
        }

        return true;
    }

   

    /* IterableInterface */

    /**
     * Get the storage iterator
     *
     * @return DbaIterator
    
    public function getIterator()
    {
        $options   = $this->getOptions();
        $namespace = $options->getNamespace();
        $prefix    = ($namespace === '') ? '' : $namespace . $options->getNamespaceSeparator();

        return new Sqlite3Iterator($this, $prefix);
    }
 */
    /* OptimizableInterface */

    /**
     * Optimize the storage
     *
     * @return bool
     * @return Exception\RuntimeException
     */
    public function optimize()
    {        
        return true;
    }

    /* reading */

    /**
     * Internal method to get an item.
     *
     * @param  string  $normalizedKey
     * @param  bool $success
     * @param  mixed   $casToken
     * @return mixed Data on success, null on failure
     * @throws Exception\ExceptionInterface
     */
    protected function internalGetItem(& $normalizedKey, & $success = null, & $casToken = null)
    {
        $internalKey = $this->_internalKey($normalizedKey) ;
        $success = false;

        $this->_getConnection() ;
        $this->_checkAndBuildStructure();
        $sql = "SELECT content FROM cache WHERE id='$internalKey'";
        
        if (! $this->getCapabilities()->getExpiredRead()) {
            $sql = $sql . " AND (expire=0 OR expire>" . time() . ')';
        }
        
        $result = $this->_query($sql);
        $row = @$result->fetch();
        
        if ($row) {
        	$success = true;
            return $row['content'];
        }

        return false;
    }

    /**
     * Internal method to test if an item exists.
     *
     * @param  string $normalizedKey
     * @return bool
     * @throws Exception\ExceptionInterface
     */
    protected function internalHasItem(& $normalizedKey)
    {
        $internalKey = $this->_internalKey($normalizedKey) ;
		
        $this->_getConnection() ;
        $this->_checkAndBuildStructure();
        
        $sql = "SELECT lastModified FROM cache WHERE id='$internalKey' AND (expire=0 OR expire>" . time() . ')';
        
        $result = $this->_query($sql);
        $row = @$result->fetch();
        if ($row) {
            return ((int) $row['lastModified']);
        }
        return false;
    }

    /* writing */

    /**
     * Internal method to store an item.
     *
     * @param  string $normalizedKey
     * @param  mixed  $value
     * @return bool
     * @throws Exception\ExceptionInterface
     */
    protected function internalSetItem(& $normalizedKey, & $value)
    {        
        $internalKey = $this->_internalKey($normalizedKey);

        $cacheableValue = (string) $value; 
        $lifetime       = $this->getOptions()->getTTl();

        $this->_getConnection();
        $this->_checkAndBuildStructure();

        $mktime = time();

        if ($lifetime == null) {
            $expire = 0;
        } else {
            $expire = $mktime + $lifetime;
        }
        
        $this->_db->beginTransaction();
        
        try {        	
        
	        $this->_query("DELETE FROM cache WHERE id='$internalKey'", true);
	        $sql = "INSERT INTO cache (id, content, lastModified, expire) VALUES ('$internalKey', ?, $mktime, $expire)";
	
	        $res = $this->_query($sql, [$value]);	
	       
	        $this->_db->commit();
	        
		} catch (Exception $e) {
			$this->_db->rollBack();
			#$this->_log("Zend_Cache_Backend_Sqlite3::save() : impossible to store the cache id=$internalKey");
			return false;
		}
        return true;
    }

    /**
     * Add an item.
     *
     * @param  string $normalizedKey
     * @param  mixed  $value
     * @return bool
     * @throws Exception\ExceptionInterface
     
    protected function internalAddItem(& $normalizedKey, & $value)
    {
       
        return parent::internalAddItem($normalizedKey, $value); 
    }*/
    
    /**
     * concatenate the necessary prefix to make a key
     * 
     * @param string $normalizedKey
     */
    protected function _internalKey($normalizedKey)
    {
    	$options     = $this->getOptions();
    	$namespace   = $options->getNamespace();
    	$prefix      = ($namespace === '') ? '' : $namespace . $options->getNamespaceSeparator();
    	$internalKey = $prefix . $normalizedKey;
    	
    	return $internalKey ;
    }

    /**
     * Internal method to remove an item.
     *
     * @param  string $normalizedKey
     * @return bool
     * @throws Exception\ExceptionInterface
     */
    protected function internalRemoveItem(& $normalizedKey)
    {        
        $internalKey = $this->_internalKey($normalizedKey);

        $this->_checkAndBuildStructure();
		$result1 = $this->_query("SELECT COUNT(*) AS nbr FROM cache WHERE id='$internalKey'");
        $result1 = $result1->fetch(PDO::FETCH_BOTH);
        if( $result1 ) {
        	$result1 = (int)@$result1[0];
        } else {
        	$result1 = 0;
        }

        $result2 = $this->_query("DELETE FROM cache WHERE id='$internalKey'", true);
        $result3 = $this->_query("DELETE FROM tag WHERE id='$internalKey'", true);
        $this->_automaticVacuum();
        
        return ($result1 && $result2 && $result3);
    }
    
    /**
     * Give (if possible) an extra lifetime to the given cache id
     *
     * @param string $id cache id
     * @param int $extraLifetime
     * @return boolean true if ok
     */
    public function touchItem($id, $extraLifetime=300)
    {    
    	$internalKey = $this->_internalKey($id) ;
    	
    	$expire = $this->_query("SELECT expire FROM cache WHERE id='$internalKey' AND (expire=0 OR expire>" . time() . ')');
    	$expire = $expire->fetch(PDO::FETCH_ASSOC);
    	$expire = $expire['expire'];
    	$newExpire = $expire + $extraLifetime;
    	$res = $this->_query("UPDATE cache SET lastModified=" . time() . ", expire=$newExpire WHERE id='$internalKey'", true);
    	if ($res) {
    		return true;
    	} else {
    		return false;
    	}
    }

    /**
     * Return an array of metadatas for the given cache id
     *
     * The array must include these keys :
     * - expire : the expire timestamp
     * - tags : a string array of tags
     * - mtime : timestamp of last modification time
     *
     * @param string $normalizedKey
     * @return array array of metadatas (false if the cache id is not found)
     * @internal param string $id cache id
     */
    public function internalGetMetadata(& $normalizedKey)
    {
    	$internalKey = $this->_internalKey($normalizedKey) ;
    	
    	$tags = [];
    	$res = $this->_query("SELECT name FROM tag WHERE id='$internalKey'");
    
    	$rows = $res->fetch(PDO::FETCH_ASSOC);
    	if($rows) {
    		foreach ($rows as $row) {
    			$tags[] = $row['name'];
    		}
    	}
    
    	$this->_query('CREATE TABLE cache (id TEXT PRIMARY KEY, content BLOB, lastModified INTEGER, expire INTEGER)', true);
    	$res = $this->_query("SELECT lastModified,expire FROM cache WHERE id='$internalKey'");
    
    	$row = @$res->fetch(PDO::FETCH_ASSOC);
    	if (!$row) return false;
    
    	return array(
    			'tags' => $tags,
    			'mtime' => $row['lastModified'],
    			'expire' => $row['expire']
    	);
    }

    /**
     * Internal method to get capabilities of this adapter
     *
     * @return Capabilities
     */
    protected function internalGetCapabilities()
    {
        if ($this->capabilities === null) {
            $marker       = new stdClass();
            $capabilities = new Capabilities(
                $this,
                $marker,
                array(
                    'supportedDatatypes' => array(
                        'NULL'     => true,
                        'boolean'  => true,
                        'integer'  => true,
                        'double'   => true,
                        'string'   => true,
                        'array'    => false,
                        'object'   => false,
                        'resource' => false,
                    ),
                    'minTtl'             => 0,
                    'supportedMetadata'  => ['mtime' , 'expire'],
                    'maxKeyLength'       => 0, // TODO: maxKeyLength ????
                	'expiredRead'        => false,
                    'namespaceIsPrefix'  => true,
                    'namespaceSeparator' => $this->getOptions()->getNamespaceSeparator(),
                )
            );

            // update namespace separator on change option
            $this->getEventManager()->attach('option', function ($event) use ($capabilities, $marker) {
                $params = $event->getParams();

                if (isset($params['namespace_separator'])) {
                    $capabilities->setNamespaceSeparator($marker, $params['namespace_separator']);
                }
            });

            $this->capabilities     = $capabilities;
            $this->capabilityMarker = $marker;
        }

        return $this->capabilities;
    }
    
    /**
     * PUBLIC METHOD FOR UNIT TESTING ONLY !
     *
     * Force a cache record to expire
     *
     * @param string $id Cache id
     */
    public function ___expire($id)
    {
    	$internalKey = $this->_internalKey($id) ;
    	###
    	$time = time() - 1;
    	$this->_query("UPDATE cache SET lastModified=$time, expire=$time WHERE id='$internalKey'", true);
    }
    
    /**
     * Return the connection resource
     *
     * If we are not connected, the connection is made
     *
     * @throws Zend_Cache_Exception
     * @return resource Connection resource
     */
    private function _getConnection()
    {
    
    	if ($this->_db) {
    		return $this->_db;
    	} else {
    
    		try{
    			$options = $this->getOptions() ;
    			$pathname = $options->getPathname() ;
    			
    			###
    			if(null == $pathname)	{
    				throw new Exception\RuntimeException('the sqlite db file/directory does not exist');
    			}
    			
    			### initiate the sqlite pdo connection
    			ErrorHandler::start(\E_ERROR);
    			$this->_db = new \PDO('sqlite:'.$pathname);    			
    			ErrorHandler::stop();
    			
    			$this->_db->query("PRAGMA journal_mode=WAL");
    			$this->_db->query("PRAGMA synchronous=NORMAL");
    
    		}catch( PDOException $ex ){
    			###
    			throw new Exception\RuntimeException($ex->getMessage());    
    		}
    		return $this->_db;
    	}
    }

    /**
     * Execute an SQL query silently
     *
     * @param string $query SQL query
     * @param bool $isExec
     * @return false|mixed query results
     */
    private function _query($query, $isExec=false)
    {    
    	$db = $this->_getConnection();
    	if ($db) {    
    
    		if($isExec == false)
    		{
    			$res = $db->prepare($query);
    			if($res)
    			{
    				$res->execute();
    			}
    
    		} elseif(is_array($isExec)) {
    			$res = $db->prepare($query);
    			if($res)
    			{
    				$res->execute($isExec);
    			}
    		} elseif($isExec == true) {
    			$res = $db->exec($query);
    
    		} else {
    			throw new Exception\RuntimeException('Unknown method');
    		}
    
    
    		if ($res === false) {
    			return false;
    		} else {
    			return $res;
    		}
    	}
    	return false;
    }
    
    
    /**
     * Deal with the automatic vacuum process
     *
     * @return void
     */
    private function _automaticVacuum()
    {
    	$options = $this->getOptions();
    	$automaticVacuumFactor = $options->getAutomaticVacuumFactor();
    	
    	if ($automaticVacuumFactor > 0) {
    		###
    		$rand = rand(1 , $automaticVacuumFactor);
    		###
    		if ($rand == 1) {
    			$this->_query('VACUUM', true);
    		}
    	}
    }
    

    /**
     * Build the database structure
     *
     * @return false
     */
    private function _buildStructure()
    {
    	$this->_query('BEGIN', true);
    	$this->_query('DROP INDEX tag_id_index', true);
    	$this->_query('DROP INDEX tag_name_index', true);
    	$this->_query('DROP INDEX cache_id_expire_index', true);
    	$this->_query('DROP TABLE version', true);
    	$this->_query('DROP TABLE cache', true);
    	$this->_query('DROP TABLE tag', true);
    	$this->_query('CREATE TABLE version (num INTEGER PRIMARY KEY)', true);
    	$this->_query('CREATE TABLE cache (id TEXT PRIMARY KEY, content BLOB, lastModified INTEGER, expire INTEGER)', true);
    	$this->_query('CREATE TABLE tag (name TEXT, id TEXT)', true);
    	$this->_query('CREATE INDEX tag_id_index ON tag(id)', true);
    	$this->_query('CREATE INDEX tag_name_index ON tag(name)', true);
    	$this->_query('CREATE INDEX cache_id_expire_index ON cache(id, expire)', true);
    	$this->_query('INSERT INTO version (num) VALUES (1)', true);
    	$this->_query('COMMIT', true);
    }
    
    /**
     * Check if the database structure is ok (with the good version)
     *
     * @return boolean True if ok
     */
    private function _checkStructureVersion()
    {
    	$result = $this->_query("SELECT num FROM version");
    	
    	if (! $result) 	{return false;}
    	
    	$row = $result->fetch(\PDO::FETCH_ASSOC);
    	
    	if (! $row) {
    		return false;
    	}
    	
    	if (((int) $row['num']) != 1) {
    		## old cache structure
    		#$this->_log('Zend\Cache\Storage\Adapter\Sqlite::_checkStructureVersion() : old cache structure version detected => the cache is going to be dropped');
    		return false;
    	}
    	
    	return true;
    }    
    
    /**
     * Check if the database structure is ok (with the good version), if no : build it
     *
     * @throws \Zend\Cache\Exception\RuntimeException 
     * @return boolean True if ok
     */
    private function _checkAndBuildStructure()
    {
    	if (! ($this->_structureChecked)) {
    		if (! $this->_checkStructureVersion()) {
    			$this->_buildStructure();
    			
    			$options = $this->getOptions();
    			$pathname = $options->getPathname();
    			
    			if (! $this->_checkStructureVersion()) {
    				throw new Exception\RuntimeException("Impossible to build cache structure in " . $pathname);
    			}
    		}
    		$this->_structureChecked = true;
    	}
    	return true;
    }
    
    protected function _close()
    {
    	$this->_db = null ;
    }

    /**
     * Clean some cache records
     *
     * Available modes are :
     * self::CLEANING_MODE_ALL (default)    => remove all cache entries ($tags is not used)
     * self::CLEANING_MODE_OLD
     *
     * @param  string $mode Clean mode
     * @return bool True if no problem
     * @internal param array $tags Array of tags
     */
    public function clean($mode = self::CLEANING_MODE_ALL)
    {
    	switch ($mode) {
    		
    		case self::CLEANING_MODE_ALL:
    			$res1 = $this->_query('DELETE FROM cache', true);
    			$res2 = $this->_query('DELETE FROM tag', true);
    			return $res1 && $res2;
    			break;
    			
    		case self::CLEANING_MODE_OLD:
    			$mktime = time();
    			$res1 = $this->_query("DELETE FROM tag WHERE id IN (SELECT id FROM cache WHERE expire>0 AND expire<=$mktime)", true);
    			$res2 = $this->_query("DELETE FROM cache WHERE expire>0 AND expire<=$mktime", true);
    			return $res1 && $res2;
    			break;
    		
    		default:
    			break;
    	}
    	$this->_automaticVacuum() ;
    	return false;
    }
}
