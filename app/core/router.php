<?php

/**
 * Class Core_Router
 *
 * @author Travis Neal
 */
class Core_Router
{
    private

        /**
         * Holds the uri of the current request
         *
         * @var string
         */
        $_raw_url,

        /**
         * Holds information about the current controller parsed from the url
         *
         * @var array|null
         */
        $_controller = null;

    /**
     * Core_Route constructor.
     *
     * Parses out the current url finds the controller based on it's findings.
     */
    public function __construct()
    {
        $this->_raw_url = App::getInstance()->request()->uri();
        $this->_parseUrl();
    }

    /**
     * Returns information about the controller.
     *
     * If a string is passed in and it's a valid key in the _controller property, it will return that value.
     * Otherwise it returns the entire _controller property.
     *
     * @param null|mixed $piece
     * @return null|mixed
     */
    public function controller($piece = null)
    {
        if (is_string($piece)) {
            if (array_key_exists($piece, $this->_controller)) {
                return $this->_controller[$piece];
            }
            return null;
        }
        return $this->_controller;
    }

    /**
     * Returns information about the method of the controller.
     *
     * If a string is passed in and it's a valid key in the _controller['method'] property, it will return that value.
     * Otherwise it returns the entire _controller['method'] property.
     *
     * @param null|mixed $piece
     * @return null|mixed
     */
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

    /**
     * Feeds data into the controller finder.
     *
     * @return $this
     */
    protected function _parseUrl()
    {
        $url_sections = array_values(array_filter(explode("/", $this->_raw_url)));
        $this->_controller = $this->_findController($url_sections);
        return $this;
    }

    /**
     * Finds controller information for the current request.
     *
     * @param array $url_sections
     * @return array
     */
    protected function _findController($url_sections)
    {
        $this->_specialControllerCases($url_sections);
        $controller_path = App::getInstance()->normalizePath("controllers", join("/", $url_sections), ".php");
        if ($controller_path === false) {
            App::getInstance()->error(404);
        }
        $inner_path = substr($controller_path, strpos($controller_path, "/controllers/") + strlen("/controllers/"), -4);
        $controller_name = str_replace(" ", "_", ucwords(str_replace("/", " ", $inner_path))) . "_Controller";
        $method_path = str_replace($inner_path, "", join("/", $url_sections));
        $method = $this->_getMethodInfo(array_values(array_filter(explode("/", $method_path))));
        return array('name' => $controller_name, 'path' => $controller_path, 'method' => $method);
    }

    /**
     * Finds information about how to use the controller.
     *
     * Parses out the rest of the url after the controller has been found to find the method name and any data that needs to be sent to it.
     *
     * @param array $method_info
     * @return array
     */
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

    /**
     * Handles special controller cases like rewrites or an empty uri.
     *
     * If a special case is found, it alters the original url sections before Core_Router::_findController() does it's actual finding.
     *
     * @param array $url_sections
     */
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

    /**
     * Checks if parts of the url match a url rewrite from the router config file.
     *
     * @param array $url_sections
     * @param array $rewrites
     * @return mixed|string
     */
    protected function _checkForRewrite($url_sections, &$rewrites)
    {
        $temp = "/" . join("/", $url_sections);
        if (array_key_exists($temp, $rewrites)) {
            $controller_parts = explode("@", $rewrites[$temp]);
            $controller_parts[0] = strtolower($controller_parts[0]); //don't lowercase the method name
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