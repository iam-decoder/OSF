<?php

/**
 * Class Core_Block
 *
 * @author Travis Neal
 */
class Core_Block
{

    protected

        /**
         * Filepath to the template to be used when calling the Core_Block::html() method.
         *
         * @var null|string
         */
        $_template = null,

        /**
         * The name of the block, used mainly for identifying within the view.
         *
         * @var null|string
         */
        $_name = null,

        /**
         * Flag for determining whether to compress the html output of the block.
         *
         * @var
         */
        $_compress;

    /**
     * Core_Block constructor.
     *
     * If a valid name is passed in, then the name is set on creation.
     *
     * @param null|string $name
     * @param bool $compress
     */
    public function __construct($name = null, $compress = false)
    {
        if (is_string($name)) {
            $this->_name = $name;
        }
        $this->_compress_output = (boolean)$compress;
    }

    /**
     * Sets the block name.
     *
     * If a string is passed in, then the block name will be set, and it will update the view.
     *
     * @param null|string $name
     * @return $this
     */
    public function name($name = null)
    {
        if (is_string($name)) {
            if (is_null($this->_name)) {
                $this->_name = $name;
            } else {
                //TODO make the Core_View::renameBlock() method. It should update _blocks and _child_tree properties
//                App::getInstance()->view()->renameBlock($this->_name, $name);
            }
        }
        return $this;
    }

    /**
     * Attaches this block to a child event in the view.
     *
     * @param string $child_name
     * @return $this
     */
    public function addTo($child_name)
    {
        App::getInstance()->view()->attachChild($child_name, $this);
        return $this;
    }

    /**
     * Allows html output compression to be turned on and off whenever necessary
     *
     * @param bool $compress
     */
    public function compressOutput($compress = true)
    {
        $this->_compress = $compress;
    }

    /**
     * Sets the template file to be used when calling the Core_Block::html() method.
     *
     * @param $path
     * @return $this
     */
    public function setTemplate($path)
    {
        $this->_template = $this->_findTemplate($path);
        return $this;
    }

    /**
     * Renders the html output of the layout.
     *
     * Calls the Core_Block::html() method and immediately echoes it out.
     *
     * @return null
     */
    public function render()
    {
        echo $this->html();
        return null;
    }

    /**
     * Returns the html output of the block.
     *
     * If there is no template set, a blank string will be returned.
     *
     * Otherwise, it will capture and return the output of the template file.
     *
     * If the html compressor is turned on (turned off by default) then it will pass the html string to the Core_Compressor::compressHtml() method and return it's output.
     *
     * @return null|string
     */
    public function html()
    {
        if ($this->_template === false) {
            return "";
        }
        ob_start();
        require($this->_template);
        $html = ob_get_contents();
        ob_end_clean();
        if ($this->_compress) {
            $compressor = new Core_Compressor($html);
            return $compressor->compressHtml();
        }
        return $html;
    }

    /**
     * Finds the template file inside the app/template directory.
     *
     * Template file names can not contain any of these characters: ['/', '.', '_', '|', ':'] or the finder will mistake them as subdirectories.
     *
     * Template files must have the extension '.phtml' to denote that it handles mainly html logic rather than data processing.
     *
     * @param string $path
     * @return bool|string
     * @throws Exception_File
     */
    protected function _findTemplate($path)
    {
        return App::getInstance()->normalizePath("template", $path, ".phtml");
    }
}