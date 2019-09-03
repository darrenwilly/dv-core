<?php
namespace DV\Cache\Storage\Adapter\Sqlite;

use Zend\Cache\Exception;
use Zend\Cache\Storage\IteratorInterface;

class Sqlite3Iterator implements IteratorInterface
{
    /**
     * The apc storage instance
     *
     * @var Sqlite3
     */
    protected $storage;

    /**
     * The iterator mode
     *
     * @var int
     */
    protected $mode = IteratorInterface::CURRENT_AS_KEY;

    /**
     * The length of the namespace prefix
     *
     * @var int
     */
    protected $prefixLength;
    
    /**
     * The namespace sprefix
     *
     * @var string
     */
    protected $prefix;

    /**
     * The current internal key
     *
     * @var string|bool
     */
    protected $currentInternalKey;

    /**
     * Constructor
     *
     * @param Dba|Sqlite3 $storage
     * @param string $prefix
     * @internal param resource $handle
     */
    public function __construct(Sqlite3 $storage , $prefix)
    {
        $this->storage      = $storage; 
        $this->prefix       = $prefix;
        $this->prefixLength = strlen($prefix);

        $this->rewind() ;
    }

    /**
     * Get storage instance
     *
     * @return Dba
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Get iterator mode
     *
     * @return int Value of IteratorInterface::CURRENT_AS_*
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Set iterator mode
     *
     * @param int $mode
     * @return Sqlite3Iterator Fluent interface
     */
    public function setMode($mode)
    {
        $this->mode = (int) $mode;
        return $this;
    }

    /* Iterator */

    /**
     * Get current key, value or metadata.
     *
     * @return mixed
     * @throws Exception\RuntimeException
     */
    public function current()
    {
        if ($this->mode == IteratorInterface::CURRENT_AS_SELF) {
            return $this;
        }

        $key = $this->key();

        if ($this->mode == IteratorInterface::CURRENT_AS_VALUE) {
            return $this->storage->getItem($key);
        } elseif ($this->mode == IteratorInterface::CURRENT_AS_METADATA) {
            return $this->storage->getMetadata($key);
        }

        return $key;
    }

    /**
     * Get current key
     *
     * @return string
     * @throws Exception\RuntimeException
     */
    public function key()
    {
        if ($this->currentInternalKey === false) {
            throw new Exception\RuntimeException("Iterator is on an invalid state");
        }

        // remove namespace prefix
        return substr($this->currentInternalKey, $this->prefixLength);
    }

    /**
     * Move forward to next element
     *
     * @return void
     * @throws Exception\RuntimeException
     */
    public function next()
    {
        if ($this->currentInternalKey === false) {
            throw new Exception\RuntimeException("Iterator is on an invalid state");
        }

        $this->currentInternalKey = next($this->getStorage()->getItems());

        // Workaround for PHP-Bug #62492
        if ($this->currentInternalKey === null) {
            $this->currentInternalKey = false;
        }
    }

    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    public function valid()
    {
        return ($this->currentInternalKey !== false);
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * @return void
     * @throws Exception\RuntimeException
     */
    public function rewind()
    {
        if ($this->currentInternalKey === false) {
            throw new Exception\RuntimeException("Iterator is on an invalid state");
        }

        $this->currentInternalKey = 0 ;
    }
}
