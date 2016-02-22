<?php

class Core_Block {

    protected
        $_template = NULL,
        $_name;

    public function __construct($name = NULL)
    {
        $this->_name = $name;
    }

    public function name($name = NULL)
    {
        if(is_string($name)){
            if(is_null($this->_name)){
                $this->_name = $name;
            } else {
                App::getInstance()->view()->renameBlock($this->_name, $name);
            }
            return $this;
        }
        return $this->_name;
    }

    public function addTo($child_name)
    {
        App::getInstance()->view()->attachChild($child_name, $this);
        return $this;
    }

    public function setTemplate($path)
    {
        $this->_template = $this->_findTemplate($path);
        return $this;
    }

    public function render()
    {
        if($this->_template !== false){
            require($this->_template);
        }
    }

    public function html()
    {
        if($this->_template === false){
            return "";
        }
        ob_start();
        require($this->_template);
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    protected function _findTemplate($path)
    {
        return App::getInstance()->normalizePath("template", $path, ".phtml");
    }
}