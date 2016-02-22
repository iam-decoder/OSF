<?php

/**
 * Class App
 *
 * @author Travis Neal
 */
class App
{

    private static

        /**
         * The singleton instance of the App and it's entirety.
         *
         * @var null|App
         */
        $_instance = null;

    protected

        /**
         * Hold's the config class for singleton access.
         *
         * @var Core_Config
         */
        $_config,

        /**
         * Hold's the view class for singleton access.
         *
         * @var Core_View
         */
        $_view,

        /**
         * Hold's the request class for singleton access.
         *
         * @var Core_Request
         */
        $_request,

        /**
         * Hold's the router class for singleton access.
         *
         * @var Core_Router
         */
        $_router,

        /**
         * Hold's the controller class for singleton access.
         *
         * @var Core_Controller
         */
        $_controller,

        /**
         * Http status codes and their HTTP/1.1 header responses.
         *
         * @var array
         */
        $_http_headers = array(
            400 => "400 Bad Request",
            401 => "401 Unauthorized",
            403 => "403 Forbidden",
            404 => "404 Not Found",
            500 => "500 Internal Server Error",
            501 => "501 Not Implemented",
            503 => "503 Service Unavailable"
        ),

        /**
         * Flag to determine whether to auto-load special app pieces or not
         *
         * @var bool
         */
        $_no_autoload = false;

    /**
     * App constructor.
     *
     * Creates the static accessor, and registers the app's class autoload function.
     */
    public function __construct()
    {
        self::$_instance = $this;
        spl_autoload_register(array($this, "registerClass"));
    }

    /**
     * Gets the current runtime instance of the application using the singleton approach.
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
     * Calls on all of the key runtime classes.
     *
     * @return null
     */
    public function run()
    {
        $this
            ->_loadRequest()
            ->_loadConfigs()
            ->_loadView()
            ->_loadRouter()
            ->_loadController()
            ->_autoloader()
            ->_startController();
    }

    /**
     * Gets the relative configuration value from the config class.
     *
     * @param string $path
     * @return mixed|null
     */
    public function config($path)
    {
        return $this->_config->get($path);
    }

    /**
     * Gets the current request's controller.
     *
     * @return Core_Controller
     */
    public function controller()
    {
        return $this->_controller;
    }

    /**
     * Gets the current request's view.
     *
     * @return Core_View
     */
    public function view()
    {
        return $this->_view;
    }

    /**
     * Kills the current request and shows an error page.
     *
     * Uses the $http_status_code input to show a special page for that error.
     *
     * If the $http_status_code is not a string or integer value, then the status code will be set to 500 to show that there was a server error.
     *
     * If there is no file in the root directory named as {$http_status_code}.php then it will recursively call App::error() again with a 500 status.
     *
     * @param int|string $http_status_code
     */
    public function error($http_status_code)
    {

        if (!is_string($http_status_code) && !is_int($http_status_code)) {
            $http_status_code = 500;
        }
        if (!headers_sent() && array_key_exists($http_status_code, $this->_http_headers)) {
            header("HTTP/1.1 {$this->_http_headers[$http_status_code]}");
        }
        if (file_exists(BASE_PATH . $http_status_code . ".php")) {
            require(BASE_PATH . $http_status_code . ".php");
        } else {
            $this->error(500);
        }

        $this->view()->autoRender(false);
        die;
    }

    /**
     * Finds the full server path to the first file according to the $filepath input.
     *
     * First attempts to get the file directly by bypassing the cascade style, if it does not find a valid file, it uses the cascade method to find the first valid file.
     *
     * In a cascading method, finds the first valid file using the directory levels defined in $filepath.
     *
     * ex. $start = 'controllers', $filepath = '/auth/login', $extension = '.php'; will return APP_PATH/controllers/auth/login.php
     * ex. $start = 'controllers', $filepath = '/auth/login/password', $extension = '.php';
     *          will return APP_PATH/controllers/auth/login.php (since it found the login.php file first)
     *          UNLESS app/controllers/auth/login/password.php is a file.
     *
     * Returns the full server path to a file. If no valid file can be found, (boolean)false will be returned instead.
     *
     * @param string $start the first directory to search in from inside the app/ directory.
     * @param string $filepath the ETRETC path of the file.
     * @param string $extension the filetype to be looking for.
     * @return bool|string
     */
    public function normalizePath($start, $filepath, $extension = ".php")
    {
        $filepath = preg_replace('/([\/._|:])/', DS, $filepath);
        $filepath_parts = array_values(array_filter(explode(DS, $filepath)));
        $concurrent = APP_PATH . (!empty($start) ? ($start . DS) : "");
        $direct_path = $concurrent . $filepath;
        if (!is_dir($direct_path) && (file_exists($direct_path) || file_exists($direct_path . $extension))) {
            return file_exists($direct_path) ? $direct_path : $direct_path . $extension;
        }
        foreach ($filepath_parts as $path) {
            $concurrent .= $path;
            if (!is_dir($concurrent) && (file_exists($concurrent) || file_exists($concurrent . $extension))) {
                return file_exists($concurrent) ? $concurrent : $concurrent . $extension;
            } elseif (is_dir($concurrent)) {
                $concurrent .= DS;
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * Gets the current request object.
     *
     * @return null|Core_Request
     */
    public function request()
    {
        return $this->_request;
    }

    /**
     * Initializes the Core_Request class.
     *
     * @return App
     */
    protected function _loadRequest()
    {
        $this->_request = new Core_Request();
        return $this;
    }

    /**
     * Initializes the Core_Config class.
     *
     * @return App
     */
    protected function _loadConfigs()
    {
        $this->_config = new Core_Config();
        return $this;
    }

    /**
     * Initializes the Core_View class.
     *
     * @return App
     */
    protected function _loadView()
    {
        $this->_view = new Core_View();
        return $this;
    }

    /**
     * Initializes the Core_Route class.
     *
     * @return App
     */
    protected function _loadRouter()
    {
        $this->_router = new Core_Router();
        return $this;
    }

    /**
     * Initializes the Core_Controller class found by Core_Router.
     *
     * @return App
     */
    protected function _loadController()
    {
        require_once($this->_router->controller('path'));
        $controller_name = $this->_router->controller('name');
        $this->_controller = new $controller_name();
        return $this;
    }

    /**
     * Auto-loads some special pieces.
     *
     * Using the configuration file app/config/autoload.php, this will load certain pieces of the application automatically for every request.
     *
     * If App::_no_autoload is true-y, then this is skipped.
     *
     * @return App
     */
    protected function _autoloader()
    {
        if(!$this->_no_autoload) {
            $layout = $this->config("autoload.layout");
            if (is_string($layout)) {
                $this->_view->setLayout($layout);
            }

            $blocks = $this->config("autoload.blocks");
            if (is_array($blocks) && count($blocks) > 0) {
                foreach ($blocks as $block) {
                    $this->_view->createBlock($block);
                }
            }
        }
        return $this;
    }

    /**
     * Starts the controller.
     *
     * Calls the method found by the router on this request's controller.
     *
     * @return App
     */
    protected function _startController()
    {
        $method_name = $this->_router->method('name');
        $method_data = $this->_router->method('data');
        if (method_exists($this->_controller, $method_name)) {
            call_user_func_array(array($this->_controller, $method_name), $method_data);
        } else {
            $this->error(404);
        }
        return $this;
    }

    /**
     * Class autoloader for the app
     *
     * @param string $classname
     * @throws Exception_File
     * @return bool
     */
    public function registerClass($classname)
    {
        $path = $this->normalizePath(null, strtolower($classname));
        if ($path === false) {
            throw new Exception_File("Could not find class " . $classname);
        }
        require_once($path);
        return true;
    }
}