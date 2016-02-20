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
        $this->_raw_url = App::getInstance()->request()->uri();
        $this->_parseUrl();
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

    // TODO: add a routes config file that maps specific urls to specific controllers
    protected function _findController($url_sections)
    {
        $this->_specialControllerCases($url_sections);
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
                App::getInstance()->error(404);
            }
        }
        return array('name' => $controller_name, 'path' => $concurrent . ".php", 'method' => $method);
    }

    protected function _getMethodInfo($method_info)
    {
        $default_method = App::getInstance()->config('routes.default_method');
        if (empty($method_info)) {
            $method_info = array((!empty($default_method) ? $default_method : "index"));
        }
        $method_name = $method_info[0];
        unset($method_info[0]);
        $method_data = array_values($method_info);
        return array('name' => $method_name, 'data' => $method_data);
    }

    protected function _specialControllerCases(&$url_sections)
    {
        if (empty($url_sections)) {
            if (App::getInstance()->config('routes.default_controller')) {
                $url_sections = explode("_", (
                substr(strtolower(App::getInstance()->config('routes.default_controller')), -11) === "_controller"
                    ? substr(App::getInstance()->config('routes.default_controller'), 0, -11)
                    : App::getInstance()->config('routes.default_controller')
                )
                );
            }
            return;
        }
        $rewrites = App::getInstance()->config('routes.rewrites');
        if (is_array($rewrites)) {
            $new_url = $this->_checkForRewrite($url_sections, $rewrites);
            if ($new_url !== $this->_raw_url) {
                $url_sections = array_values(array_filter(explode("/", $new_url)));
            }
        }
    }

    protected function _checkForRewrite($url_sections, &$rewrites)
    {
        $temp = "/" . join("/", $url_sections);
        if (array_key_exists($temp, $rewrites)) {
            $controller_parts = explode("@", $rewrites[$temp]);
            $contorller_parts[0] = strtolower($controller_parts[0]); //don't lowercase the method name
            if (substr($controller_parts[0], -11) === "_controller") {
                $controller_parts[0] = substr($controller_parts[0], 0, -11);
            }
            return str_replace("_", "/", join("_", $controller_parts));
        } elseif (!empty($url_sections)) {
            $end = array_pop($url_sections);
            return $this->_checkForRewrite($url_sections, $rewrites) . "/" . $end;
        } else {
            return "";
        }
    }
}