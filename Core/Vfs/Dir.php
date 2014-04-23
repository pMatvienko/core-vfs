<?php

/**
 * Core_Vfs_Dir.
 *
 * @extends Core_Vfs_Abstract
 * @class   Core_Vfs_Dir
 * @author  PMatvienko
 * @version 1.0
 * @package Core_Vfs
 *
 * @todo    Extend functionality
 */
class Core_Vfs_Dir extends Core_Vfs_Abstract
{
    /**
     * Constructor.
     *
     * @param null|string $path Path to use.
     */
    public function __construct($path = null)
    {
        if ($path != null) {
            $this->_source = self::defineAdapterResource($path);
            if (!$this->_source->isExists()) {
                $this->_source->createDir($this->_source->getPath(), 0777, true);
            }
        }
    }

    /**
     * Copying current directory to given directory.
     *
     * @param Core_Vfs_Dir $directoryToCopyTo Directory instance to copy to.
     * @param null|string  $newName           Optional. New file name. Pass it if you want to rename copied file.
     * @param string       $actionControl     Action control for is_exists situation.
     *
     * @return boolean|Core_Vfs_File
     */
    public function copyTo(Core_Vfs_Dir $directoryToCopyTo, $newName = null, $actionControl = self::ACTION_REPLACE)
    {
        if ($newName == null) {
            $newName = explode('/', $this->_getSource()->getPath());
            $newName = $newName[(count($newName) - 2)];
        }
        $createdDir = $directoryToCopyTo->_getSource()->getNewInstance(
            $directoryToCopyTo->_getSource()->getPath() . $newName
        );

        if (!$createdDir->isDir()) {
            $createdDir = $directoryToCopyTo->createDir($newName);
            if (!($createdDir instanceof Core_Vfs_Dir)) {
                return false;
            }
        } else {
            $createdDir = Core_Vfs_Abstract::factory($createdDir);
        }
        foreach ($this->getList() as $item) {
            $copyResult = $item->copyTo($createdDir, null, $actionControl);
            if (!$copyResult) {
                if ($actionControl == self::ACTION_ABORT) {
                    return false;
                }
            }
        }

        return $createdDir;
    }

    /**
     * Moving current file to given directory.
     *
     * @param Core_Vfs_Dir $directoryToMoveTo Directory instance to copy to.
     * @param null|string  $newName           Optional. New file name. Pass it if you want to rename copied file.
     * @param string       $actionControl     Action control for is_exists situation.
     *
     * @return boolean|Core_Vfs_File
     */
    public function moveTo(Core_Vfs_Dir $directoryToMoveTo, $newName = null, $actionControl = self::ACTION_REPLACE)
    {
        if ($newName == null) {
            $newName = explode('/', $this->_getSource()->getPath());
            $newName = $newName[(count($newName) - 2)];
        }

        $createdDir = $directoryToMoveTo->_getSource()->getNewInstance(
            $directoryToMoveTo->_getSource()->getPath() . $newName
        );
        if (!$createdDir->isDir()) {
            $createdDir = $directoryToMoveTo->createDir($newName);
            if (!($createdDir instanceof Core_Vfs_Dir)) {
                return false;
            }
        } else {
            $createdDir = new Core_Vfs_Dir(null, $createdDir);
        }

        $removeThisFolder = true;

        foreach ($this->getList() as $item) {
            if (!$item->moveTo($createdDir, null, $actionControl)) {
                switch ($actionControl) {
                    case self::ACTION_ABORT:
                        return false;

                    case self::ACTION_SKIP:
                        $removeThisFolder = false;
                        break;

                    default:
                        $removeThisFolder = true;
                        break;
                }
            }
        }
        if ($removeThisFolder) {
            $this->remove();
        }

        return $createdDir;
    }

    /**
     * Gets a list of sub items of current directory.
     *
     * @return Core_Vfs_Iterator Array of Core_Vfs_Dir and Core_Vfs_File items.
     * @throws Zend_Exception If direcory does not exists by given path.
     */
    public function getList()
    {
        if (!$this->_getSource()->isExists()) {
            Zend_Loader::loadClass('Zend_Exception');
            throw new Zend_Exception('Path "' . $this->_getSource()->getPath() . '" does not exists');
        }

        return new Core_Vfs_Iterator($this->_getSource()->getList());
    }

    /**
     * Changing path for current directory.
     *
     * @param string $directory Directory path, absolute or relative for current directory. ../ Allowed.
     *
     * @return boolean|Core_Vfs_Dir
     */
    public function changeDir($directory)
    {
        $currentPath = $this->_getSource()->getPath();

        if (substr($currentPath, -1) == '/') {
            $currentPath = substr($currentPath, 0, (strlen($currentPath) - 1));
        }

        $pathToSet = $currentPath;
        if ($directory == '../') {
            $pathToSet = dirname($pathToSet);
        } elseif (substr($directory, 0, 1) == '/' || stristr($directory, ':/')) {
            $pathToSet = $directory;
        } else {
            $pathToSet .= '/' . $directory;
        }
        /*$this
            ->_getSource()
            ->setPath($pathToSet);*/
        $changedDir = Core_Vfs_Abstract::factory($pathToSet);
        if ($changedDir == null || !$changedDir->_getSource()->isDir()) {
            return false;
        } else {
            return $changedDir;
        }
    }

    /**
     * Creates a directory by given path that will be relative to current directory.
     *
     * @param string $directory  Directory path to create.
     * @param string $accessMode Created directory access mode.
     *
     * @return boolean|Core_Vfs_Dir
     * @throws Zend_Exception If directory path has invalid names.
     */
    public function createDir($directory, $accessMode = 0777)
    {
        $directory = str_replace('\\', '/', $directory);
        $matched   = preg_match('~([\w./_-]+)~', $directory, $matches);
        if (!$matched || empty($matches[1]) || $matches[1] != $directory) {
            Zend_Loader::loadClass('Zend_Exception');
            throw new Zend_Exception('Directory name "' . $directory . '" is invalid');
        }

        $result = $this
            ->_getSource()
            ->createDir($directory, $accessMode);
        if ($result != false) {
            $dir          = new Core_Vfs_Dir();
            $dir->_source = $result;

            return $dir;
        }

        return false;
    }
}