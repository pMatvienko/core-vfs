<?php

/**
 * Core_Vfs_Abstract Base functionality for Core_Vfs.
 *
 * @abstract
 * @class   Core_Vfs_Abstract
 * @author  PMatvienko
 * @version 1.0
 * @package Core_Vfs
 * @todo    Extend functionality
 */
abstract class Core_Vfs_Abstract
{
    const ACTION_REPLACE = 'replace';
    const ACTION_SKIP    = 'skip';
    const ACTION_ABORT   = 'abort';

    const ADAPTER_LOCAL = 'local';

    /**
     *
     * @var Core_Vfs_Adapter_Interface
     */
    protected $_source = null;

    /**
     * Constructor.
     *
     * @param null|string $path Path to use.
     */
    public function __construct($path = null)
    {
        if ($path != null) {
            $this->_source = self::defineAdapterResource($path);
        }
    }

    /**
     * Factory method to simply create Core_Vfs_File and Core_Vfs_Dir instances by existing path.
     *
     * @param string $path Path.
     *
     * @return Core_Vfs_Dir|Core_Vfs_File|null
     */
    public static function factory($path = null)
    {
        if (!($path instanceof Core_Vfs_Adapter_Interface)) {
            $adapter = self::defineAdapterResource($path);
        } else {
            $adapter = $path;
        }
        if ($adapter->isFile()) {
            $out = new Core_Vfs_File();
        } elseif ($adapter->isDir()) {
            $out = new Core_Vfs_Dir();
        } else {
            return null;
        }
        $out->_source = $adapter;

        return $out;
    }

    /**
     * Gets a source adapter.
     *
     * @return Core_Vfs_Adapter_Interface
     * @throws Zend_Exception On error.
     */
    protected function _getSource()
    {
        if ($this->_source == null) {
            Zend_Loader::loadClass('Zend_Exception');
            throw new Zend_Exception('This Core_Vfs instance has no valid adapter to use.');
        }

        return $this->_source;
    }

    /**
     * Gets an adapter to use by parsing a full path.
     *
     * @param string $path Full path in format protocol://user:pass@host:port/[some path].
     *
     * @return Core_Vfs_Adapter_Interface
     * @throws Zend_Exception If adapter can not be defined.
     */
    public static function defineAdapterResource($path)
    {
        $path = explode(':/', $path);
        if (count($path) < 2) {
            $adapter = Core_Vfs_Adapter_Manager::LOCAL;
            $path    = $path[0];
        } elseif (strlen($path[0]) == 1) {
            $adapter = Core_Vfs_Adapter_Manager::LOCAL;
            $path    = implode(':/', $path);
        } else {
            $adapter = $path[0];
            unset($path[0]);
            $path = implode(':/', $path);
        }

        $adapter = Core_Vfs_Adapter_Manager::getInstance()->getAdapter(ucfirst($adapter));
        $adapter->setPath(str_replace('\\', '/', $path));

        return $adapter;
    }

    /**
     * Removing current directory.
     *
     * @return boolean
     */
    public function remove()
    {
        $result = $this->_getSource()->remove();
        if ($result) {
            $this->_source = null;
        }

        return $result;
    }

    /**
     * Gets an info about current instance path item.
     *
     * @return Core_Vfs_Info
     */
    public function getInfo()
    {
        $source            = $this->_getSource();
        $info              = array(
            'creationTime' => $source->getCreationTime(),
            'lastAccessTime' => $source->getLastAccessTime(),
            'lastEditTime' => $source->getLastEditTime(),
            'permissions' => $source->getPermissions(),
            'size' => $source->getSize(),
            'type' => $source->getFileType(),
            'isWritable' => $source->getIsReadable(),
            'isReadable' => $source->getIsWritable()
        );
        $pathInfo          = $source->getPathInfo();
        $info['directory'] = $pathInfo['dirname'];
        $info['name']      = $pathInfo['filename'];
        $info['extension'] = (empty($pathInfo['extension']) ? null : $pathInfo['extension']);

        return new Core_Vfs_Info($info);
    }

    /**
     * Gets a local path to an item. Without adapter resource prefix.
     *
     * @return string
     */
    public function getLocalPath()
    {
        return $this->_getSource()->getPath();
    }

    /**
     * Gets a path to an item.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->_getSource()->getAdapterResourceName() . ':/' . $this->_getSource()->getPath();
    }

    /**
     * Gets an adapter resource name.
     *
     * @return string
     */
    public function getAdapterResource()
    {
        return $this->_getSource()->getAdapterResourceName();
    }

    /**
     *  Checking is item in local file system.
     *
     * @return boolean
     */
    public function isInLocalFs()
    {
        return ($this->_getSource() instanceof Core_Vfs_Adapter_Local);
    }

    /**
     * Copying current file to given directory.
     *
     * @param Core_Vfs_Dir $directoryToCopyTo Directory instance to copy to.
     * @param null|string  $newName           Optional. New file name. Pass it if you want to rename copied file.
     * @param string       $actionControl     Action control for is_exists situation.
     *
     * @return boolean|Core_Vfs_File
     */
    abstract public function copyTo(
        Core_Vfs_Dir $directoryToCopyTo, $newName = null, $actionControl = self::ACTION_REPLACE
    );

    /**
     * Moving current file to given directory.
     *
     * @param Core_Vfs_Dir $directoryToMoveTo Directory instance to copy to.
     * @param null|string  $newName           Optional. New file name. Pass it if you want to rename copied file.
     * @param string       $actionControl     Action control for is_exists situation.
     *
     * @return boolean|Core_Vfs_File
     */
    abstract public function moveTo(
        Core_Vfs_Dir $directoryToMoveTo, $newName = null, $actionControl = self::ACTION_REPLACE
    );
}