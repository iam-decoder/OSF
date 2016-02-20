<?php

/**
 * Class Core_Router
 */
class Core_Router
{
    private

        /**
         * @var string
         */
        $_raw_url,

        /**
         * @var array|null
         */
        $_controller = null;

    /**
     * Core_Route constructor.
     */
    public function __construct()
    {
        $this->_raw_url = App::request()->uri();
        $this->_parseUrl();
    }

    /**
     * Sets http status to 404, and displays the 404 page.
     */
    public function show404()
    {
        $sapi_type = php_sapi_name();
        if (substr($sapi_type, 0, 3) == 'cgi') {
            header("Status: 404 Not Found");
        } else {
            header("HTTP/1.1 404 Not Found");
        }
        App::view('errors.404');
        die;
    }

    public function controller($piece = null)
    {
        if (!is_null($piece)) {
            if (array_key_exists($piece, $this->_controller)) {
                return $this->_controller[$piece];
            }
            return null;
        }
        return $this->_controller;
    }

    public function method($piece = null)
    {
        if (!is_null($piece)) {
            if (array_key_exists($piece, $this->_controller['method'])) {
                return $this->_controller['method'][$piece];
            }
            return null;
        }
        return $this->_controller['method'];
    }

    protected function _parseUrl()
    {
        $url_pieces = array_values(array_filter(explode("/", $this->_raw_url)));
        $this->_controller = $this->_findController($url_pieces);
    }

    protected function _findController($url_sections
    ) // TODO: add a routes config file that maps specific urls to specific controllers
    {
        if (empty($url_sections) && App::config('routes.default_controller')) {
            $url_sections = explode("_", (
            substr(strtolower(App::config('routes.default_controller')), -11) === "_controller"
                ? substr(App::config('routes.default_controller'), 0, -11)
                : App::config('routes.default_controller')
            )
            );
        }
        $url_sections = array_values($url_sections); //ensure that we're dealing with indexed arrays starting at 0
        $after_controller_found = $url_sections;
        $concurrent = APP_PATH . "controllers" . DS;
        $controller_name = "";
        foreach (array_values($url_sections) as $i => $path) {
            $path = strtolower($path);
            $controller_name .= ucfirst($path);
            $concurrent .= $path;
            unset($after_controller_found[$i]);
            if (file_exists($concurrent . ".php")) {
                $controller_name .= "_Controller";
                $method = $this->_getMethodInfo(array_values($after_controller_found)); //reset the array indexes after removing controller path info
                break;
            } elseif (is_dir($concurrent)) {
                $controller_name .= "_";
                $concurrent .= DS;
            } else {
                $this->show404();
            }
        }
        return array('name' => $controller_name, 'path' => $concurrent . ".php", 'method' => $method);
    }

    protected function _getMethodInfo($method_info)
    {
        $default_method = App::config('routes.default_method');
        if (empty($method_info)) {
            $method_info = array((!empty($default_method) ? $default_method : "index"));
        }
        $method_name = $method_info[0];
        unset($method_info[0]);
        $method_data = array_values($method_info);
        return array('name' => $method_name, 'data' => $method_data);
    }
}