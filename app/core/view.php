<?php

class Core_View
{

    public function __construct()
    {
    }

    public function load($path, $return = false)
    {
        $viewfile = $this->_findView($path);
        if ($return) {
            ob_start();
        }
        require($viewfile);
        if ($return) {
            $contents = ob_get_contents();
            ob_end_flush();
            return $contents;
        }
    }

    protected function _findView($viewpath)
    {
        $viewpath = explode("/", preg_replace('/[.]/', "/", $viewpath));
        $concurrent = APP_PATH . "views" . DS;
        foreach ($viewpath as $path) {
            $concurrent .= $path;
            if (file_exists($concurrent . ".php")) {
                return $concurrent . ".php";
            } elseif (is_dir($concurrent)) {
                $concurrent .= DS;
            } else {
                throw new Exception_View("Could not find view file located at views" . DS . $viewpath);
            }
        }
    }
}