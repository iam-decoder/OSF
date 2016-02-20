<?php

class Core_Request
{
    protected
        $_input,
        $_get = array(),
        $_post = array(),
        $_put = array();


    public function __construct()
    {
        $this->_input = file_get_contents("php://input");
        $this->_populateParameters();
    }

    public function isAjax()
    {
        // TODO: generate tokens with a lifespan that get passed back and forth on each request to the server.
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtoupper($_SERVER['HTTP_X_REQUESTED_WITH']) === "XMLHTTPREQUEST");
    }

    public function method()
    {
        //always uppercase, default to get
        return strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : "GET");
    }

    public function isPost()
    {
        return $this->method() === "POST";
    }

    public function isGet()
    {
        return $this->method() === "GET";
    }

    public function isPut()
    {
        return $this->method() === "PUT";
    }


    public function host()
    {
        if (isset($_SERVER['SERVER_NAME'])) {
            return $_SERVER['SERVER_NAME'];
        } elseif (isset($_SERVER['HTTP_HOST'])) {
            return $_SERVER['HTTP_HOST'];   //Can be change from client headers.
        }
        return null;
    }

    public function ssl()
    {
        if (isset($_SERVER['HTTPS'])) {
            return !empty($_SERVER['HTTPS']);
        } elseif (isset($_SERVER['SERVER_PORT'])) {
            return (string)$_SERVER['SERVER_PORT'] === '443';
        } elseif (isset($_SERVER['REQUEST_SCHEME'])) {
            return strtolower($_SERVER['REQUEST_SCHEME']) === 'https';
        }
        return null;
    }

    public function uri()
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            $questionMarkIndex = strpos($_SERVER['REQUEST_URI'], '?');
            if ($questionMarkIndex !== false) {
                return rtrim(substr($_SERVER['REQUEST_URI'], 0, $questionMarkIndex), '/');
            }
            return rtrim($_SERVER['REQUEST_URI'], '/');
        }
        return null;
    }

    public function query()
    {
        return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
    }

    public function baseUrl()
    {
        $host = $this->host();
        if (!empty($host)) {
            return "http" . ($this->ssl() ? "s" : "") . "://$host/";
        }
        return null;
    }

    public function url()
    {
        $base = $this->baseUrl();
        $uri = $this->uri();
        if (!empty($base) && !empty($uri)) {
            return rtrim($base, '/') . $uri;
        }
        return null;
    }

    public function fullUrl()
    {
        $url = $this->url();
        $query_string = $this->query();
        if (!empty($url) && $this->isGet() && !empty($query_string)) {
            return rtrim($url, '/') . "?$query_string";
        } elseif (!empty($url) && $this->isGet()) { // in case server fills querystring on POST requests as well.
            return $url;
        }
        return null;
    }

    public function ip()
    {
        // Don't use HTTP_X_FORWARDED_FOR or HTTP_CLIENT_IP as they can be changed by the client.
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }

    public function get($path = null)
    {
        if (is_string($path)) {
            $path = preg_replace("/([.|:-])/", "/", $path);
            $path = explode("/", $path);
        }
        return is_array($path) ? $this->_valueByLevel($this->_get, array_values($path)) : $this->_get;
    }

    public function post($path = null)
    {
        if (is_string($path)) {
            $path = preg_replace("/([.|:-])/", "/", $path);
            $path = explode("/", $path);
        }
        return is_array($path) ? $this->_valueByLevel($this->_post, array_values($path)) : $this->_get;
    }

    public function put($path = null)
    {
        if (is_string($path)) {
            $path = preg_replace("/([.|:-])/", "/", $path);
            $path = explode("/", $path);
        }
        return is_array($path) ? $this->_valueByLevel($this->_post, array_values($path)) : $this->_get;
    }

    protected function _valueByLevel($array, $path)
    {
        if (is_array($path) && !empty($path)) {
            $key = array_shift($path);
            if (array_key_exists($key, $array)) {
                return $this->_valueByLevel($array[$key], $path);
            } else {
                return null;
            }
        }
        return $array;
    }

    protected function _populateParameters()
    {
        switch ($this->method()) {
            case 'GET':
                $this->_get = $_GET;
                break;
            case 'POST':
                $this->_post = $this->_parseInput();
                break;
            case 'PUT':
                $this->_put = $this->_parseInput();
                break;
        }
    }

    protected function _parseInput()
    {
        $input_vars = array();
        parse_str($this->_input, $input_vars);
        return $input_vars;
    }
}