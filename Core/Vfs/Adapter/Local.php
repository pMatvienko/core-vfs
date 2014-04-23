<?php

/**
 * Core_Vfs_Adapter_Local Adapter to work with local file system.
 *
 * @class   Core_Vfs_Adapter_Local
 * @author  PMatvienko
 * @version 1.0
 * @package Core_Vfs
 * @todo    Extend functionality
 */
class Core_Vfs_Adapter_Local implements Core_Vfs_Adapter_Interface
{
    protected $_path = null;
    protected $_adapterResourceName;

    /**
     * Gets a mime data for current item.
     *
     * @return stdClass
     * @throws Core_Vfs_Exception If current item is not a file.
     */
    public function getMime()
    {
        if (!$this->isFile()) {
            Zend_Loader::loadClass('Zend_Exception');
            throw new Core_Vfs_Exception('"' . $this->getPath() . '" Is not a file.');
        }
        $finfo          = finfo_open(FILEINFO_MIME);
        $fileMimeString = finfo_file($finfo, $this->getPath());
        finfo_close($finfo);
        $out               = new stdClass();
        $fileMimeString    = explode(';', $fileMimeString);
        $fileMimeString[0] = explode('/', trim($fileMimeString[0]));
        $out->type         = $fileMimeString[0][0];
        $out->subtype      = $fileMimeString[0][1];
        $out->charset      = trim(str_replace('charset=', '', $fileMimeString[1]));

        return $out;
    }

    /**
     * Constructor.
     *
     * @param null|array $options Adapter options.
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            foreach ($options as $option => $value) {
                if (method_exists($this, 'set' . ucfirst($option))) {
                    $this->{'set' . ucfirst($option)}($value);
                }
            }
        }
    }

    /**
     * Gets a last access time.
     *
     * @return integer
     */
    public function getLastAccessTime()
    {
        return fileatime($this->getPath());
    }

    /**
     * Gets a last edit time.
     *
     * @return integer
     */
    public function getLastEditTime()
    {
        return filemtime($this->getPath());
    }

    /**
     * Gets a creation time.
     *
     * @return integer
     */
    public function getCreationTime()
    {
        return filectime($this->getPath());
    }

    /**
     * Gets a permissions.
     *
     * @return integer
     */
    public function getPermissions()
    {
        return fileperms($this->getPath());
    }

    /**
     * Gets size in bytes.
     *
     * @return integer
     */
    public function getSize()
    {
        return filesize($this->getPath());
    }

    /**
     * Gets an item type.
     *
     * @return string
     */
    public function getFileType()
    {
        return filetype($this->getPath());
    }

    /**
     * Gets a path info for item.
     *
     * @return array
     */
    public function getPathInfo()
    {
        return pathinfo($this->getPath());
    }

    /**
     * Gets is Item readable.
     *
     * @return boolean
     */
    public function getIsReadable()
    {
        return is_writeable($this->getPath());
    }

    /**
     * Gets is file writable.
     *
     * @return boolean
     */
    public function getIsWritable()
    {
        return is_writable($this->getPath());
    }

    /**
     * Adds file from local file system to current directory.
     *
     * @param string $localFile Path to a local file.
     * @param string $newName   Name for copied file.
     *
     * @return boolean|Core_Vfs_Adapter_Local
     * @throws Core_Vfs_Exception If given path is not a dir.
     */
    public function addFileFromLocalFs($localFile, $newName)
    {
        if (!$this->isDir()) {
            throw new Core_Vfs_Exception('"' . $this->getPath() . '" Is not a directory.');
        }
        $path   = $this->getPath() . $newName;
        $result = copy($localFile, $path);
        if ($result) {
            $result = new Core_Vfs_Adapter_Local(array('path'                => $path,
                                                       'adapterResourceName' => Core_Vfs_Adapter_Manager::LOCAL));
        }

        return $result;
    }

    /**
     * Copying File to given path. Used for copying form remote file systems.
     *
     * @param string $filePath Path to copy to.
     *
     * @return boolean
     * @throws Core_Vfs_Exception If given path is not a dir.
     */
    public function putFileToTemp($filePath)
    {
        if (!$this->isFile()) {
            Zend_Loader::loadClass('Zend_Exception');
            throw new Core_Vfs_Exception('"' . $this->getPath() . '" Is not a file.');
        }

        return copy($this->getPath(), $filePath);
    }

    /**
     * Removing current item.
     *
     * @return boolean
     */
    public function remove()
    {
        if ($this->isFile()) {
            return unlink($this->getPath());
        } elseif ($this->isDir()) {
            return $this->_removeDir($this->getPath());
        }

        return false;
    }

    /**
     * Gets a new Instance of current adapter.
     *
     * @param null|string $path Path to set.
     *
     * @return Core_Vfs_Adapter_Interface
     */
    public function getNewInstance($path = null)
    {
        return new Core_Vfs_Adapter_Local(
            array(
                'path'                => $path,
                'adapterResourceName' => $this->_adapterResourceName
            )
        );
    }

    /**
     * Removing directory with all entries.
     *
     * @param string $path Path for directory to remove.
     *
     * @return boolean
     */
    protected function _removeDir($path)
    {
        if (substr($path, -1) != '/') {
            $path .= '/';
        }
        $dirCursor = opendir($path);
        while ($item = readdir($dirCursor)) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (is_file($path . $item)) {
                if (!unlink($path . $item)) {
                    return false;
                }
            } else {
                if (!$this->_removeDir($path . $item)) {
                    return false;
                }
            }
        }
        closedir($dirCursor);

        return rmdir($path);
    }

    /**
     * Creating a directory.
     *
     * @param string $directoryName Path of created directory.
     * @param string $accessMode    Access mode.
     *
     * @return boolean|Core_Vfs_Adapter_Local
     * @throws Core_Vfs_Exception If given path is not a dir.
     */
    public function createDir($directoryName, $accessMode, $newInstance = false)
    {
        if (!$newInstance) {
            if (!$this->isDir()) {
                throw new Core_Vfs_Exception('"' . $this->getPath() . '" Is not a directory.');
            }
            $path = $this->getPath() . $directoryName;
        } else {
            $path = $directoryName;
        }

        $checkPath = new Core_Vfs_Adapter_Local(array('path'                => $path,
                                                      'adapterResourceName' => Core_Vfs_Adapter_Manager::LOCAL));
        $result    = false;
        if (!$checkPath->isExists()) {
            $result = mkdir($path, $accessMode, true);
        }
        if ($result) {
            return new Core_Vfs_Adapter_Local(array('path'                => $path,
                                                    'adapterResourceName' => Core_Vfs_Adapter_Manager::LOCAL));
        } else {
            return false;
        }
    }

    /**
     * Gets a sub items list for directory in current path.
     *
     * @return array
     * @throws Core_Vfs_Exception If given path is not a dir.
     */
    public function getList()
    {
        if (!$this->isDir()) {
            throw new Core_Vfs_Exception('"' . $this->getPath() . '" Is not a directory.');
        }
        $path      = $this->getPath();
        $openedDir = opendir($path);

        $itemsList = array();
        while ($item = readdir($openedDir)) {
            if ($item == '..' || $item == '.') {
                continue;
            }
            $itemsList[] = new Core_Vfs_Adapter_Local(array('path' => $path . $item));
        }

        return $itemsList;
    }

    /**
     * Gets a path of current instance.
     *
     * @return string
     */
    public function getPath()
    {
        $path = $this->_path;
        if ($this->isDir()) {
            if (substr($path, -1) != '/') {
                $path .= '/';
            }
        }

        return $path;
    }

    /**
     * Sets a path for current instance.
     *
     * @param string $path Path to use.
     *
     * @return Core_Vfs_Adapter_Ftp
     */
    public function setPath($path)
    {
        $this->_path = str_replace('\\', '/', $path);

        return $this;
    }

    /**
     * Checking is given path exists.
     *
     * @return boolean
     */
    public function isExists()
    {
        $result = false;
        if ($this->isFile() || $this->isDir()) {
            $result = true;
        }

        return $result;
    }

    /**
     * Checking is given path a file.
     *
     * @return boolean
     */
    public function isFile()
    {
        return is_file($this->_path);
    }

    /**
     * Checking is given path a dir.
     *
     * @return boolean
     */
    public function isDir()
    {
        return is_dir($this->_path);
    }

    /**
     *  Sets a resource name.
     *
     * @param string $name Resource name.
     *
     * @return Core_Vfs_Adapter_Local
     */
    public function setAdapterResourceName($name)
    {
        $this->_adapterResourceName = $name;

        return $this;
    }

    /**
     *  Gets a resource Name.
     *
     * @return string
     */
    public function getAdapterResourceName()
    {
        return $this->_adapterResourceName;
    }
}