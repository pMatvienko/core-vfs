<?php

/**
 * Core_Vfs_File.
 *
 * @extends Core_Vfs_Abstract
 * @class   Core_Vfs_File
 * @author  PMatvienko
 * @version 1.0
 * @package Core_Vfs
 * @todo    Extend functionality
 */
class Core_Vfs_File extends Core_Vfs_Abstract
{
    /**
     * Gets a directory that contains current file.
     *
     * @return Core_Vfs_Dir
     */
    public function getDirectory()
    {
        $path = $this->getPath();
        $path = str_replace('\\', '/', $path);
        $path = substr($path, 0, strrpos($path, '/'));

        return Core_Vfs_Abstract::factory($path);
    }

    /**
     *  Outputs a raw file contents to browser (Without any headers).
     *
     * @return boolean
     */
    public function outRaw()
    {
        $source = $this->_getSource();
        if (!($source instanceof Core_Vfs_Adapter_Local)) {
            $temp = tempnam(sys_get_temp_dir(), 'Core_Vfs_Temp');
            if (!$this->_getSource()->putFileToTemp($temp)) {
                return false;
            }
            $temp = new Core_Vfs_Adapter_Local(array('path' => $temp));
            $path = $temp->getPath();
        } else {
            $path = $this->_getSource()->getPath();
        }
        $cursor = fopen($path, 'r');
        $row    = fgets($cursor);
        while ($row) {
            echo $row;
            $row = fgets($cursor);
        }
        fclose($cursor);
        if (isset($temp)) {
            $temp->remove();
        }
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
    public function copyTo(Core_Vfs_Dir $directoryToCopyTo, $newName = null, $actionControl = self::ACTION_REPLACE)
    {
        if ($newName == null) {
            $newName = explode('/', $this->_getSource()->getPath());
            $newName = $newName[(count($newName) - 1)];
        }
        $newPath = $directoryToCopyTo->_getSource()->getNewInstance(
            $directoryToCopyTo->_getSource()->getPath() . $newName
        );

        if ($newPath->isFile()) {
            switch ($actionControl) {
                case self::ACTION_REPLACE:
                    $newPath->remove();
                    break;

                default:
                    return false;
                    break;
            }
        }

        if (get_class($this->_getSource()) == 'Core_Vfs_Adapter_Local') {
            $result = $directoryToCopyTo->_getSource()->addFileFromLocalFs($this->_getSource()->getPath(), $newName);
        } else {
            $tempFile = tempnam(sys_get_temp_dir(), 'Core_Vfs_Temp');

            if (!$this->_getSource()->putFileToTemp($tempFile)) {
                return false;
            }

            $result = $directoryToCopyTo->_getSource()->addFileFromLocalFs($tempFile, $newName);
            unlink($tempFile);
        }
        if ($result != false) {
            $obj = new Core_Vfs_File(null);
            $obj->_source = $result;
            $result = $obj;
        }

        return $result;
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
        $result = $this->copyTo($directoryToMoveTo, $newName, $actionControl);
        if ($result) {
            $this->remove();
        }

        return $result;
    }

    /**
     * Gets a file mime info.
     *
     * @return stdClass
     */
    public function getInfoMime()
    {
        $mime = $this->_getSource()->getMime();
        if ($mime == null) {
            $path = tempnam(sys_get_temp_dir(), 'Core_Vfs_Temp');
            if (!$this->_getSource()->putFileToTemp($path)) {
                return false;
            }
            $path = new Core_Vfs_Adapter_Local(array('path' => $path));
            $mime = $path->getMime();
            $path->remove();
        }

        return $mime;
    }
}