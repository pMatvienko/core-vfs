<?php
/**
 * @category   Core
 * @package    Core_Application
 * @subpackage Resource
 */

require_once 'Zend/Application/Resource/ResourceAbstract.php';

/**
 * Cache Manager resource
 *
 * @uses       Zend_Application_Resource_ResourceAbstract
 * @category   Core
 * @package    Core_Application
 * @subpackage Resource
 */
class Core_Application_Resource_Vfs extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * @var Core_Cron_Manager
     */
    protected $_manager = null;


    /**
     * Initialize Cron_Manager.
     *
     * @return Core_Cron_Manager
     */
    public function init()
    {
        return $this->getVfsAdaptersManager();
    }


    /**
     * Retrieve Core_Cron_Manager instance.
     *
     * @return Core_Cron_Manager
     */
    public function getVfsAdaptersManager()
    {
        $options = $this->getOptions();
        $manager = Core_Vfs_Adapter_Manager::getInstance();
        if (!empty($options['adapters'])) {
            foreach ($options['adapters'] as $name => $params) {
                $manager->setAdapterConfig($name, $params);
            }
        }
        $autocreate  = !empty($options['autocreate']) && $options['autocreate'] == true;
        $pathManager = Core_Vfs_Path_Manager::getInstance($autocreate);
        $pathManager->setPathes($options['pathes']);

        return $pathManager;
    }
}