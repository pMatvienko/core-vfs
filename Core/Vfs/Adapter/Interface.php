<?php

/**
 * Core_Vfs_Adapter_Interface Interface for Core_Vfs adapters.
 *
 * @interface
 * @class   Core_Vfs_Adapter_Interface
 * @author  PMatvienko
 * @version 1.0
 * @package Core_Vfs
 * @todo    Extend functionality
 */
interface Core_Vfs_Adapter_Interface
{
    /**
     * Constructor.
     *
     * @param null|array $options Adapter options.
     */
    public function __construct($options = null);

    /**
     * Gets a mime data for current item.
     *
     * @return stdClass|null
     * @throws Core_Vfs_Exception If current item is not a file.
     */
    public function getMime();

    /**
     * Gets a last access time.
     *
     * @return integer
     */
    public function getLastAccessTime();

    /**
     * Gets a last edit time.
     *
     * @return integer
     */
    public function getLastEditTime();

    /**
     * Gets a creation time.
     *
     * @return integer
     */
    public function getCreationTime();

    /**
     * Gets a permissions.
     *
     * @return integer
     */
    public function getPermissions();

    /**
     * Gets size in bytes.
     *
     * @return integer
     */
    public function getSize();

    /**
     * Gets an item type.
     *
     * @return string
     */
    public function getFileType();

    /**
     * Gets a path info for item.
     *
     * @return array
     */
    public function getPathInfo();

    /**
     * Gets is Item readable.
     *
     * @return boolean
     */
    public function getIsReadable();

    /**
     * Gets is file writable.
     *
     * @return boolean
     */
    public function getIsWritable();

    /**
     * Gets a new Instance of current adapter.
     *
     * @param null|string $path Path to set.
     *
     * @return Core_Vfs_Adapter_Interface
     */
    public function getNewInstance($path = null);

    /**
     * Copying File to given path. Used for copying form remote file systems.
     *
     * @param string $filePath Path to copy to.
     *
     * @return boolean
     * @throws Zend_Exception If current instance is not a file.
     */
    public function putFileToTemp($filePath);

    /**
     * Adds file from local file system to current directory.
     *
     * @param string $localFile Path to a local file.
     * @param string $newName   Name for copied file.
     *
     * @return boolean|Core_Vfs_Adapter_Local
     */
    public function addFileFromLocalFs($localFile, $newName);

    /**
     * Removing current item.
     *
     * @return boolean
     */
    public function remove();

    /**
     * Creating a directory.
     *
     * @param string $directoryName Path of created directory.
     * @param string $accessMode    Access mode.
     *
     * @return boolean|Core_Vfs_Adapter_Local
     */
    public function createDir($directoryName, $accessMode, $newInstance = false);

    /**
     * Gets a sub items list for directory in current path.
     *
     * @return array
     */
    public function getList();

    /**
     * Gets a path of current instance.
     *
     * @return string
     */
    public function getPath();

    /**
     * Sets a path for current instance.
     *
     * @param string $path Path to use.
     *
     * @return Core_Vfs_Adapter_Ftp
     */
    public function setPath($path);

    /**
     * Checking is given path exists.
     *
     * @return boolean
     */
    public function isExists();

    /**
     * Checking is given path a file.
     *
     * @return boolean
     */
    public function isFile();

    /**
     * Checking is given path a dir.
     *
     * @return boolean
     */
    public function isDir();

    /**
     *  Sets a resource name.
     *
     * @param string $name Resource name.
     *
     * @return Core_Vfs_Adapter_Local
     */
    public function setAdapterResourceName($name);

    /**
     *  Gets a resource Name.
     *
     * @return string
     */
    public function getAdapterResourceName();
}