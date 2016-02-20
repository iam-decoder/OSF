<?php

/**
 * Class Core_Config
 */
class Core_Config
{
    private
        /**
         * @var array
         */
        $_loaded = array();

    protected
        /**
         * @var array
         */
        $_instance_cache = array();

    /**
     * Core_Config constructor.
     */
    public function __construct()
    {
        $this->_loaded = array();
        $this->_instance_cache = array();
        $config_files = array_values(preg_grep('/^(.*\.php)$/', scandir(APP_PATH . "config" . DS)));
        foreach ($config_files as $file) {
            $this->_loadConfig($file);
        }
    }

    /**
     * Gets the config that has been loaded for the app.
     * Retreives in a cascading return pattern where
     * the $path must start with the filename in
     * the config folder
     *
     * ex. $path = "app" will return the entire array from the `app/config/app.php` file.
     * ex. $path = "app.root_url" will return the contents corresponding to the `root_url` key inside the `app/config/app.php` file.
     *
     * If the key being returned is an array,
     * you can append more `.$key`s to
     * the $path to cascade further
     * into the config return.
     *
     * If the $path cascade can't find
     * the requested value, it will
     * return null
     *
     * @param $path string
     * @return mixed|null
     */
    public function get($path)
    {
        if (array_key_exists($path, $this->_instance_cache)) {
            return $this->_instance_cache[$path];
        }
        if (is_string($path)) {
            $pieces = explode(".", $path);
            $current_level = $this->_loaded;
            foreach ($pieces as $level) {
                if (array_key_exists($level, $current_level)) {
                    $current_level = $current_level[$level];
                }
            }
            return $this->_instance_cache[$path] = $current_level;
        }
        return $this->_instance_cache[$path] = null;
    }

    /**
     * Loads all .php files from the app/config folder
     * into the $_loaded property. all config files
     * must return a value.
     *
     * @param $file string
     * @return bool
     */
    protected function _loadConfig($file)
    {
        $path = APP_PATH . "config" . DS . $file;
        if (file_exists($path)) {
            $info = pathinfo($path);
            $this->_loaded[$info["filename"]] = require_once($info['dirname'] . DS . $info['basename']);
            return true;
        }
        return false;
    }
}