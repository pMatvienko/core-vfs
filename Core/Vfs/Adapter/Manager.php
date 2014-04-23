<?php

class Core_Vfs_Adapter_Manager
{
    CONST LOCAL = 'local';

    protected static $_instance = null;

    protected static $_protocolAdaptersClassPrefix = 'Core_Vfs_Adapter_';

    /**
     *  Gets a manager instance.
     *
     * @return Core_Vfs_Adapter_Manager
     */
    static public function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     *  Config.
     *
     * @var array
     */
    protected $_allAdaptersConfig = array();

    /**
     *  Constructor.
     */
    protected function __construct()
    {
        $this->setAdapterConfig(self::LOCAL, array('adapter' => 'local'));
    }

    /**
     *  Sets a config for adapter resource.
     *
     * @param string $adapterName   Adapter resource name.
     * @param array  $adapterParams Adapter resource params.
     *
     * @return Core_Vfs_Adapter_Manager
     */
    public function setAdapterConfig($adapterName, array $adapterParams)
    {
        $this->_allAdaptersConfig[ucfirst($adapterName)] = $adapterParams;

        return $this;
    }

    /**
     *  Gets an adpater resource configured from config file.
     *
     * @param string $adapter Resource name.
     *
     * @return Core_Vfs_Adapter_Interface
     *
     * @throws Core_Vfs_Exception If there are no such resource set.
     */
    public function getAdapter($adapter)
    {
        if (!isset($this->_allAdaptersConfig[$adapter])) {
            Zend_Loader::loadClass('Core_Vfs_Exception');
            throw new Core_Vfs_Exception('Adapter config for "' . $adapter . '" adapter not found.');
        }
        $class = self::$_protocolAdaptersClassPrefix . ucfirst($this->_allAdaptersConfig[$adapter]['adapter']);

        $options                        = isset($this->_allAdaptersConfig[$adapter]['options'])
            ? $this->_allAdaptersConfig[$adapter]['options'] : array();
        $options['adapterResourceName'] = strtolower($adapter);

        return new $class($options);
    }
}