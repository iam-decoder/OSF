<?php

/**
 * Class App
 */
class App
{

    private static

        /**
         * @var null|App
         */
        $_instance = null;

    protected

        /**
         * @var Core_Config
         */
        $_config,

        /**
         * @var Core_View
         */
        $_view,

        /**
         * @var Core_Request
         */
        $_request,

        /**
         * @var Core_Router
         */
        $_router,

        /**
         * @var Core_Controller
         */
        $_controller;

    /**
     * App constructor.
     */
    public function __construct()
    {
        self::$_instance = $this;
        spl_autoload_register(array($this, "registerClass"));
    }

    /**
     * Gets the current runtime instance of the application using the singleton approach
     *
     * @return App
     */
    public static function &getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Calls on all of the key runtime classes
     *
     * Available as both static and non-static
     *
     * @return null
     */
    public function run()
    {
        if (!(isset($this) && get_class($this) === __CLASS__)) {
            return self::getInstance()->{__FUNCTION__}();
        }
        $this->_loadRequest();
        $this->_loadConfigs();
        $this->_loadView();
        $this->_loadRouter();
        $this->_loadController();
        $this->_startController();
    }

    /**
     * Gets the relative configuration value from the config class
     *
     * Available as both static and non-static
     *
     * @param $path
     * @return null|array|string
     */
    public function config($path)
    {
        if (!(isset($this) && get_class($this) === __CLASS__)) {
            return self::getInstance()->{__FUNCTION__}($path);
        }
        return $this->_config->get($path);
    }

    /**
     * Gets the current request's controller
     *
     * Available as both static and non-static
     *
     * @param $path
     * @return null|array|string
     */
    public function controller()
    {
        if (!(isset($this) && get_class($this) === __CLASS__)) {
            return self::getInstance()->{__FUNCTION__}();
        }
        return $this->_controller;
    }

    /**
     * Gets the current request's controller
     *
     * Available as both static and non-static
     *
     * @param $path
     * @return null|array|string
     */
    public function view($path, $return = false)
    {
        if (!(isset($this) && get_class($this) === __CLASS__)) {
            return self::getInstance()->{__FUNCTION__}($path, $return);
        }
        return $this->_view->load($path, $return);
    }

    /**
     * Gets the current request's controller
     *
     * Available as both static and non-static
     *
     * @return null|Core_Request
     */
    public function request()
    {
        if (!(isset($this) && get_class($this) === __CLASS__)) {
            return self::getInstance()->{__FUNCTION__}();
        }
        return $this->_request;
    }

    /**
     * System loading complete, ready to start business logic
     *
     * @return null
     */
    protected function _startController()
    {
        $method_name = $this->_router->method('name');
        $method_data = $this->_router->method('data');
        if (method_exists($this->_controller, $method_name)) {
            call_user_func_array(array($this->_controller, $method_name), $method_data);
        }
    }

    /**
     * Initializes the config class and loads configurations
     *
     * @return null
     */
    protected function _loadConfigs()
    {
        $this->_config = new Core_Config();
    }

    /**
     * Initializes the config class and loads configurations
     *
     * @return null
     */
    protected function _loadView()
    {
        $this->_view = new Core_View();
    }

    /**
     * Initializes the config class and loads configurations
     *
     * @return null
     */
    protected function _loadRequest()
    {
        $this->_request = new Core_Request();
    }

    /**
     * Initializes the route class
     *
     * @return null
     */
    protected function _loadRouter()
    {
        $this->_router = new Core_Router();
    }

    /**
     * Initializes the config class and loads configurations
     *
     * @return null
     */
    protected function _loadController()
    {
        require_once($this->_router->controller('path'));
        $controller_name = $this->_router->controller('name');
        $this->_controller = new $controller_name();
    }

    /**
     * Class autoloader for the app
     *
     * @param $classname string
     * @return bool
     */
    public function registerClass($classname)
    {
        $path = explode('_', strtolower($classname));
        $concurrent = APP_PATH;
        foreach ($path as $level) {
            $concurrent .= $level;
            if (file_exists($concurrent . ".php")) {
                require_once($concurrent . ".php");
                return true;
            } elseif (file_exists($concurrent . DS)) {
                $concurrent .= DS;
            } else {
                return false;
            }
        }
        return false;
    }
}