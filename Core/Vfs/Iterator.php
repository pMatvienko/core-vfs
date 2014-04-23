<?php

/**
 * @package Core_Vfs
 * @author  P.Matvienko
 * @project W.A.C.
 * @class   Core_Vfs_Iterator
 *
 */
class Core_Vfs_Iterator implements ArrayAccess, Iterator, Countable
{
    protected $_enteries = array();
    protected $_position = 0;

    /**
     * Constructor.
     *
     * @param array $list List of enteries.
     */
    public function __construct(array $list)
    {
        $this->_enteries = $list;
    }

    /**
     * OffcetExists Implementation.
     *
     * @param string $offset Offset.
     *
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->_enteries[$offset]);
    }

    /**
     * Gets an item by given offset.
     *
     * @param string $offset Offset.
     *
     * @return Core_Vfs_Directory|Core_Vfs_File
     */
    public function offsetGet($offset)
    {
        if (is_string($this->_enteries[$offset])) {
            $this->_enteries[$offset] = Core_Vfs_Abstract::factory($this->_enteries[$offset]);
        } elseif ($this->_enteries[$offset] instanceof Core_Vfs_Adapter_Interface) {
            $this->_enteries[$offset] = Core_Vfs_Abstract::factory($this->_enteries[$offset]);
        }

        return $this->_enteries[$offset];
    }

    /**
     * Sets an item to given offset.
     *
     * @param string $offset Offset.
     * @param mixed  $value  Item to set.
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->_enteries[$offset] = $value;
    }

    /**
     * Unset item by given offset.
     *
     * @param string $offset Offset.
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->_enteries[$offset]);
    }

    /**
     * Gets current item.
     *
     * @return Core_Vfs_Directory|Core_Vfs_File
     */
    public function current()
    {
        return $this->offsetGet($this->_position);
    }

    /**
     * Iterator Next implementation.
     *
     * @return void
     */
    public function next()
    {
        $this->_position += 1;
    }

    /**
     * Iterator key implementation.
     *
     * @return integer
     */
    public function key()
    {
        return $this->_position;
    }

    /**
     * Iterator Valid implementation.
     *
     * @return boolean
     */
    public function valid()
    {
        return isset($this->_enteries[$this->_position]);
    }

    /**
     * Iterator rewind implementation.
     *
     * @return void
     */
    public function rewind()
    {
        $this->_position = 0;
    }

    /**
     * Countable count implementation.
     *
     * @return integer
     */
    public function count()
    {
        return count($this->_enteries);
    }
}