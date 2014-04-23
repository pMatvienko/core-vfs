<?php

/**
 * @package Core_Vfs
 * @author  P.Matvienko
 * @project W.A.C.
 *
 */
class Core_Vfs_Path_Manager
{
    protected static $_self = null;

    /**
     * Gets an instance.
     *
     * @param bool $autoCreate
     *
     * @internal param array $pathes
     *
     * @return Core_Vfs_Path_Manager
     */
    public static function getInstance($autoCreate = false)
    {
        if (self::$_self == null) {
            self::$_self = new self($autoCreate);
        }

        return self::$_self;
    }

    /**
     * Constructor.
     *
     * @param bool $autoCreate
     *
     * @internal param array $pathes
     */
    protected function __construct($autoCreate = false)
    {
        $this->_autoCreate = $autoCreate;
    }

    protected function __clone()
    {

    }

    /**
     * Pathes.
     *
     * @var array
     */
    protected $_pathes = array();

    protected $_autoCreate = false;

    /**
     * Set pathes.
     *
     * @param array $pathes
     *
     * @return Core_Vfs_Path_Manager
     */
    public function setPathes($pathes)
    {
        $this->reset();
        foreach ($pathes as $name => $path) {
            $this->addPath($name, $path);
        }

        return $this;
    }

    /**
     * Adding a path.
     *
     * @param string $name
     * @param string $path
     *
     * @return Core_Vfs_Path_Manager
     */
    public function addPath($name, $path)
    {
        $this->_pathes[$name] = $path;

        return $this;
    }

    /**
     * @param $name
     *
     * @return $this
     */
    public function removePath($name)
    {
        if ($this->pathIsSet($name)) {
            unset($this->_pathes[$name]);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->_pathes = array();

        return $this;
    }

    public function pathIsSet($name)
    {
        return !empty($this->_pathes[$name]);
    }

    /**
     *
     * @param type $name
     *
     * @return Core_Vfs_Dir
     * @throws Core_Vfs_Exception
     */
    public function pathGet($name)
    {
        if (empty($this->_pathes[$name])) {
            throw new Core_Vfs_Exception('Path not defined for key "' . $name . '"');
        }
        if (!($this->_pathes[$name] instanceof Core_Vfs_Abstract)) {
            $vfs = Core_Vfs_Abstract::factory($this->_pathes[$name]);

            if ($vfs == null) {
                if ($this->_autoCreate) {
                    $vfs = new Core_Vfs_Dir($this->_pathes[$name]);
                }
            }
            if ($vfs == null) {
                throw new Core_Vfs_Exception('Path not created for key "' . $name . '"');
            } else {
                $this->_pathes[$name] = $vfs;
            }
        }
        if (!($this->_pathes[$name] instanceof Core_Vfs_Abstract)) {
            throw new Core_Vfs_Exception('Path not created for key "' . $name . '"');
        }

        return $this->_pathes[$name];
    }

    public function __get($name)
    {
        return $this->pathGet($name);
    }


    public function __set($name, $path)
    {
        $this->addPath($name, $path);
    }
}
