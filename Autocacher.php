<?php

/**
 * Zend_Controller_Plugin_Autocacher
 *
 * @author taketin
 * @since 2009
 */

class  Zend_Controller_Plugin_Autocacher extends Zend_Controller_Plugin_Abstract
{

    /** autocacher setting config object */
    private $_config;

    /**
     * read annotation
     *
     * @param obj $request Zend_Controller_Request_Abstract
     * @return void
     */
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        /** autocacher setting file */
        $this->_config = new Zend_Config_Ini('../application/configs/autocacher.ini');

        /** session for config object */
        $session = new Zend_Session_Namespace($this->_config->basic->session_name);

        $controllerName = ucwords($request->getControllerName()) . 'Controller';
        $actionName = $request->getActionName() . 'Action';
        if ('on' == $this->_config->basic->modules) {
            $moduleName = $request->getModuleName();
            $path = '../application/modules/' . $moduleName . '/controllers/';
        } else {
            $path = '../application/controllers/';
        }

        /** get taeget class source */
        include_once($path . $controllerName . '.php');
        $method = new ReflectionMethod($controllerName, $actionName);
        $docComment = $method->getDocComment();
        $annotation = explode(' ', substr($docComment, strpos($docComment, '@config')));

        /** get config name from annotation */
        foreach ($annotation as $key => $val) {
            $firstStr = substr(trim($val), 0, 1);
            if ('@' != $firstStr && '*' != $firstStr && '' != $firstStr) {
                $configs[] = trim($val);
            }
        }

        /** do caching */
        foreach ($configs as $key => $config) {
            $session->{$config} = $this->_caching($config);
        }
    }

    /**
     * do caching
     *
     * @param string $config
     * @return obj Zend_Config Object
     */
    private function _caching($config)
    {
        /** cache_dir make  */
        if (!is_Dir($this->_config->basic->cache_dir)) {
            mkdir($this->_config->basic->cache_dir);
        }

        $cache = Zend_Cache::factory('File', 'File',
                array(
                        'lifetime'                => NULL,
                        'automatic_serialization' => true,
                        'master_file'             => $this->_config->basic->config_dir . $config . '.ini'
                ),
                array(
                        'cache_dir'               => $this->_config->basic->cache_dir
                )
        );

        if (!($configCache = $cache->load($config))) {
            $configCache = new Zend_Config_Ini($this->_config->basic->config_dir . $config . '.ini');
            $cache->save($configCache, $config);
        }

        return $configCache;
    }
}
