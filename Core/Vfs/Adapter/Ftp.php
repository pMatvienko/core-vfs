<?php

/**
 * Core_Vfs_Adapter_Ftp Adapter to work with FTP file system.
 *
 * @class   Core_Vfs_Adapter_Ftp
 * @author  PMatvienko
 * @version 1.0
 * @package Core_Vfs
 * @todo    Extend functionality
 */
class Core_Vfs_Adapter_Ftp implements Core_Vfs_Adapter_Interface
{
    protected $_remoteHost = null;
    protected $_remotePort = 21;
    protected $_remoteUser = 'anonymous';
    protected $_remotePassword = 'P.M.FtpClient';
    protected $_remotePath = null;
    protected $_remoteItemType = null;

    protected static $_itemNotExists = 'NOT_EXISTS';
    protected static $_itemIsDir = 'DIRECTORY';
    protected static $_itemIsFile = 'FILE';

    protected static $_connection = array();

    protected $_adapterResourceName;

    /**
     * Constructor.
     *
     * @param null|array $options Adapter options.
     *
     * @throws Core_Vfs_Exception On Error.
     */
    public function __construct($options = null)
    {
        if (!function_exists('ftp_connect')) {
            throw new Core_Vfs_Exception('Ftp extension not enabled.');
        }
        foreach ($options as $option => $value) {
            if (method_exists($this, 'set' . ucfirst($option))) {
                $this->{'set' . ucfirst($option)}($value);
            }
        }
    }

    /**
     * Gets a mime data for current item.
     *
     * @return null
     * @throws Core_Vfs_Exception If current item is not a file.
     */
    public function getMime()
    {
        if (!$this->isFile()) {
            Zend_Loader::loadClass('Zend_Exception');
            throw new Core_Vfs_Exception('"' . $this->getPath() . '" Is not a file.');
        }

        return null;
    }

    /**
     * Gets a file size from remote server.
     *
     * @return null|float
     * @throws Core_Vfs_Exception If Current instance path is not a file.
     */
    public function getSize()
    {
        ob_start();
        $size = ftp_size($this->_getConnection(), $this->getPath());
        ob_end_clean();
        if ($size == -1 || $size === false) {
            return null;
        }

        return $size;
    }

    /**
     * Gets a last modified time.
     *
     * @return integer|null
     * @throws Core_Vfs_Exception If Current instance path is not a file.
     */
    public function getlastModifiedTime()
    {
        if (!$this->isFile()) {
            throw new Core_Vfs_Exception('"' . $this->getPath() . '" Is not a file.');
        }
        ob_start();
        $time = ftp_size($this->_getConnection(), $this->getPath());
        ob_end_clean();
        if ($time == -1 || $time === false) {
            $time = null;
        }

        return $time;
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
        $result = ftp_put($this->_getConnection(), $path, $localFile, FTP_BINARY);
        if ($result) {
            $result = $this->getNewInstance($path);
        }

        return $result;
    }

    /**
     * Copying File to given path. Used for copying form remote file systems.
     *
     * @param string $filePath Path to copy to.
     *
     * @return boolean
     * @throws Core_Vfs_Exception If given path is not a file.
     */
    public function putFileToTemp($filePath)
    {
        if (!$this->isFile()) {
            throw new Core_Vfs_Exception('"' . $this->getPath() . '" Is not a file.');
        }
        ob_start();
        $result = ftp_get($this->_getConnection(), $filePath, $this->getPath(), FTP_BINARY);
        ob_end_clean();

        return $result;
    }

    /**
     * Removing current item.
     *
     * @return boolean
     */
    public function remove()
    {
        if ($this->isFile()) {
            ob_start();
            $result = ftp_delete($this->_getConnection(), $this->getPath());
            ob_end_clean();

            return $result;
        } elseif ($this->isDir()) {
            return $this->_removeDir($this->getPath());
        }

        return false;
    }

    /**
     * Removing directory with all enteries.
     *
     * @param string $path Path for directory to remove.
     *
     * @return boolean
     */
    protected function _removeDir($path)
    {
        foreach ($this->getList() as $item) {
            if (!$item->remove()) {
                return false;
            }
        }
        ob_start();
        $result = ftp_rmdir($this->_getConnection(), $path);
        ob_end_clean();

        return $result;
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
        if (!$this->isDir()) {
            throw new Core_Vfs_Exception('"' . $this->getPath() . '" Is not a directory.');
        }

        $selfPath = $path = $this->getPath();
        $temp     = $this->getNewInstance($path . $directoryName);
        $result   = false;
        if (!$temp->isExists()) {
            $directoryName = explode('/', $directoryName);
            ob_start();
            foreach ($directoryName as $namePart) {
                if (!ftp_chdir($this->_getConnection(), $path . $namePart)) {
                    ftp_chdir($this->_getConnection(), $path);
                    if (!ftp_mkdir($this->_getConnection(), $namePart)) {
                        return false;
                    }
                }
                $path .= $namePart . '/';
            }
            ob_end_clean();
            $result = true;
        }
        $this->setPath($selfPath);

        if ($result) {
            $result = $this->getNewInstance($path);
        }

        return $result;
    }

    /**
     * Gets a Core_Vfs_Adapter_Ftp with given path and settings from current adapter.
     *
     * @param string $path Path to set.
     *
     * @return Core_Vfs_Adapter_Ftp
     */
    public function getNewInstance($path = null)
    {
        $adapter = new Core_Vfs_Adapter_Ftp(
            array(
                'remoteHost'          => $this->_remoteHost,
                'remotePort'          => $this->_remotePort,
                'remoteUser'          => $this->_remoteUser,
                'remotePassword'      => $this->_remotePassword,
                'adapterResourceName' => $this->_adapterResourceName
            )
        );
        if ($path != null) {
            $adapter->setPath($path);
        }

        return $adapter;
    }

    /**
     * Gets a sub items list for directory in current path.
     *
     * @return array Array of Core_Vfs_Adapter_Ftp instances.
     * @throws Core_Vfs_Exception If given path is not a dir.
     */
    public function getList()
    {
        if (!$this->isDir()) {
            throw new Core_Vfs_Exception('"' . $this->getPath() . '" Is not a directory.');
        }
        ob_start();
        $list = ftp_nlist($this->_getConnection(), $this->getPath());
        ob_end_clean();
        $out = array();
        if (is_array($list)) {
            foreach ($list as $item) {
                if (strrchr($item, '/') == '/.' || strrchr($item, '/') == '/..' || strrchr($item, '/') == '/') {
                    continue;
                }
                $out[] = $this->getNewInstance($item);
            }
        }

        return $out;
    }

    /**
     * Gets a path of current instance.
     *
     * @return string
     */
    public function getPath()
    {
        $path = $this->_remotePath;
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
        $path = str_replace('\\', '/', $path);
        if (substr($path, 0, 1) != '/') {
            $path = '/' . $path;
        }
        $this->_remoteItemType = null;
        $this->_remotePath     = $path;

        return $this;
    }

    /**
     * Checking is given path exists.
     *
     * @return boolean
     */
    public function isExists()
    {
        if ($this->_remoteItemType == null) {
            $result = ($this->isDir() || $this->isFile());

            if (!$result) {
                $this->_remoteItemType = self::$_itemNotExists;
            }
        }

        return $this->_remoteItemType != self::$_itemNotExists;
    }

    /**
     * Checking is given path a file.
     *
     * @return boolean
     */
    public function isFile()
    {
        if ($this->_remoteItemType == null) {
            ob_start();
            try {
                $result = ftp_size($this->_getConnection(), $this->_remotePath);
                if ($result == -1 || $result === false) {
                    $result = false;
                } elseif ($result === 0) {
                    $chDirResult = ftp_chdir($this->_getConnection(), $this->_remotePath);
                    if (!$chDirResult) {
                        $result = true;
                    } else {
                        $result = false;
                    }
                } else {
                    $result = true;
                }
                if ($result) {
                    $this->_remoteItemType = self::$_itemIsFile;
                }
            } catch (Exception $e) {
                $result = false;
            }
            ob_end_clean();
        }

        return $this->_remoteItemType == self::$_itemIsFile;
    }

    /**
     * Checking is given path a dir.
     *
     * @return boolean
     */
    public function isDir()
    {
        if ($this->_remoteItemType == null) {
            ob_start();
            try {
                $result = ftp_chdir($this->_getConnection(), $this->_remotePath);
            } catch (Exception $e) {
                $result = false;
            }
            if ($result) {
                $this->_remoteItemType = self::$_itemIsDir;
            }
            ob_end_clean();
        }

        return $this->_remoteItemType == self::$_itemIsDir;
    }

    /**
     * Gets a connection resource.
     *
     * @return resource
     */
    protected function _getConnection()
    {
        if (!isset(self::$_connection[$this->_remoteUser . ':' . $this->_remotePassword . '@' . $this->_remoteHost . ':'
        . $this->_remotePort])
        ) {
            self::$_connection[
            $this->_remoteUser . ':' . $this->_remotePassword . '@' . $this->_remoteHost . ':' . $this->_remotePort]
                = $this->_connect();
        }

        return self::$_connection[$this->_remoteUser . ':' . $this->_remotePassword . '@' . $this->_remoteHost . ':'
        . $this->_remotePort]->connection;
    }

    /**
     * Gets a root path for used connection.
     *
     * @return string.
     */
    protected function _getRootPath()
    {
        if (!isset(self::$_connection[$this->_remoteUser . ':' . $this->_remotePassword . '@' . $this->_remoteHost . ':'
        . $this->_remotePort])
        ) {
            self::$_connection[
            $this->_remoteUser . ':' . $this->_remotePassword . '@' . $this->_remoteHost . ':' . $this->_remotePort]
                = $this->_connect();
        }

        return self::$_connection[$this->_remoteUser . ':' . $this->_remotePassword . '@' . $this->_remoteHost . ':'
        . $this->_remotePort]->rootPath;
    }

    /**
     * Connecting to remote host.
     *
     * @return stdClass StdClass with connection and root path.
     * @throws Zend_Exception On Error.
     */
    protected function _connect()
    {
        $connection = ftp_connect($this->_remoteHost, $this->_remotePort);
        if (!$connection) {
            Zend_Loader::loadClass('Zend_Exception');
            throw new Zend_Exception('Can not connect to ftp host "' . $this->_remoteHost . ':' . $this->_remotePort
            . '"');
        }
        if ($this->_remoteUser !== null && $this->_remotePassword !== null) {
            ftp_login($connection, $this->_remoteUser, $this->_remotePassword);
        }
        ftp_pasv($connection, true);
        $out             = new stdClass();
        $out->connection = $connection;
        $out->rootPath   = ftp_pwd($connection);

        return $out;
    }

    /**
     * Setter for remote host.
     *
     * @param string $host Remote host.
     *
     * @return Core_Vfs_Adapter_Ftp
     */
    public function setRemoteHost($host)
    {
        $this->_remoteHost = $host;

        return $this;
    }

    /**
     * Setter for remote port.
     *
     * @param integer $port Port.
     *
     * @return Core_Vfs_Adapter_Ftp
     */
    public function setRemotePort($port)
    {
        $this->_remotePort = intval($port);

        return $this;
    }

    /**
     * Setter for remote user login.
     *
     * @param string $user User login.
     *
     * @return Core_Vfs_Adapter_Ftp
     */
    public function setRemoteUser($user)
    {
        $this->_remoteUser = $user;

        return $this;
    }

    /**
     * Setter for remote password.
     *
     * @param string $password Password.
     *
     * @return Core_Vfs_Adapter_Ftp
     */
    public function setRemotePassword($password)
    {
        $this->_remotePassword = $password;

        return $this;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        if (isset(self::$_connection[$this->_remoteUser . ':' . $this->_remotePassword . '@' . $this->_remoteHost . ':'
        . $this->_remotePort])
        ) {
            ftp_close(
                self::$_connection[$this->_remoteUser . ':' . $this->_remotePassword . '@' . $this->_remoteHost . ':'
                . $this->_remotePort]->connection
            );
            unset(self::$_connection[$this->_remoteUser . ':' . $this->_remotePassword . '@' . $this->_remoteHost . ':'
            . $this->_remotePort]);
        }
    }

    /**
     * Gets a last access time.
     *
     * @return integer
     */
    public function getLastAccessTime()
    {
        return $this->getLastEditTime();
    }

    /**
     * Gets a last edit time.
     *
     * @return integer
     */
    public function getLastEditTime()
    {
        return ftp_mdtm($this->_getConnection(), $this->getPath());
    }

    /**
     * Gets a creation time.
     *
     * @return integer
     */
    public function getCreationTime()
    {
        return $this->getLastEditTime();
    }

    /**
     * Gets a permissions.
     *
     * @return integer
     */
    public function getPermissions()
    {
        return null;
    }

    /**
     * Gets an item type.
     *
     * @return string
     */
    public function getFileType()
    {
        if ($this->isDir()) {
            return 'dir';
        } elseif ($this->isFile()) {
            return 'file';
        } else {
            return null;
        }
    }

    /**
     * Gets a path info for item.
     *
     * @return array
     */
    public function getPathInfo()
    {
        $path = $this->getPath();
        if (substr($path, -1) == '/') {
            $path = substr($path, 0, strlen($path) - 1);
        }
        $out = array
        (
            'dirname'   => dirname($path),
            'filename'  => substr(strrchr($path, '/'), 1),
            'extension' => null
        );
        $ext = strrchr($out['filename'], '.');
        if (!empty($ext)) {
            $out['extension'] = substr($ext, 1);
            $out['filename']  = str_replace($ext, '', $out['filename']);
        }

        return $out;
    }

    /**
     * Gets is Item readable.
     *
     * @return boolean
     */
    public function getIsReadable()
    {
        return true;
    }

    /**
     * Gets is file writable.
     *
     * @return boolean
     */
    public function getIsWritable()
    {
        return true;
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