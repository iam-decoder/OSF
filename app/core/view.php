<?php

/**
 * Class Core_View
 *
 * @author Travis Neal
 */
class Core_View
{
    protected

        /**
         * Holds the children branches for how to output blocks using getChildHtml().
         *
         * @var array|null
         */
        $_child_tree = null,

        /**
         * Holds all the blocks added to the view.
         *
         * @var array|null
         */
        $_blocks = null,

        /**
         * Flag for determining whether to compress the html output of the view.
         *
         * @var bool
         */
        $_compress = true,

        /**
         * Flag for determining whether to auto-render on class destruction or not.
         *
         * @var bool
         */
        $_rendered = false,

        /**
         * Holds the path of the layout file to be used in the Core_View::html() and Core_View::render() methods
         *
         * @var null|string
         */
        $_layout = null;

    /**
     * Core_View constructor.
     * @param bool $compress
     */
    public function __construct($compress = true)
    {
        $this->_child_tree = array();
        $this->_blocks = array();
        $this->_compress = $compress;
    }

    /**
     * Attaches a block to a child event of the view.
     *
     * If the block is a string it will look through previously added blocks and recursively calls attachChild() again with the block instance.
     *
     * If the block is a derivative of Core_Block, it will attach it to the child event to be appended when calling getChildHtml().
     *
     * If the child event did not previously exist, it will create a new child event and attach the block to it.
     *
     * @param string $child
     * @param Core_Block|string $block
     * @return $this
     */
    public function attachChild($child, &$block)
    {
        if (is_string($child)) {
            if ($block instanceof Core_Block) {
                if (!array_key_exists($child, $this->_child_tree)) {
                    $this->_child_tree[$child] = array($block);
                } elseif (!in_array($block, $this->_child_tree[$child])) {
                    $this->_child_tree[$child][] = $block;
                }
            } elseif (is_string($block)) {
                return $this->attachChild($child, $this->getBlock($block));
            }
        }
        return $this;
    }

    /**
     * Adds a retrievable block to the view and creates a child event for it.
     *
     * A Block MUST have a name in order to be added to the view, otherwise there's no point in adding it because there's no way to retrieve it.
     *
     * A child event will also be created using the block's name as the name of the child event, but only if the child event does not exist already.
     *
     * @param $block
     * @return bool|Core_Block
     * @throws Exception_View
     */
    public function addBlock(&$block)
    {
        if ($block instanceof Core_Block) {
            if (!$block->name()) {
                throw new Exception_View("Blocks must be named in order to attach it to the view.");
            } elseif (array_key_exists($block->name(), $this->_blocks)) {
                throw new Exception_View("Block [{$block->name()}] already exists. Rename the block or attach as an anonymous block to the current one.");
            }
            $this->_blocks[$block->name()] = $block;
            if (!array_key_exists($block->name(), $this->_child_tree)) {
                $this->_child_tree[$block->name()] = array();
            }
            return $block;
        }
        return false;
    }

    /**
     * Create's a block using the input given.
     *
     * If the $block_class given can be mapped to a class name in the app/blocks folder, it will load that specific block.
     *
     * If it can't find a block class, it will create an anonymous Core_Block and name it with the contents of $block_class
     *
     * @param null|string $block_class the name of the Class of a block, or the name to be set on an anonymous block
     * @return Core_Block
     * @throws Exception_View
     */
    public function createBlock($block_class = null)
    {
        if (is_string($block_class)) {
            $class_fix_for_path = strtolower($block_class);
            if (substr($class_fix_for_path, -6) !== "_block") {
                $block_class .= "_Block";
            } else {
                $class_fix_for_path = substr($class_fix_for_path, 0, -6);
            }
            $block_path = $this->_findBlock($class_fix_for_path);
            if ($block_path !== false) {
                require_once($block_path);
                $block = new $block_class();
                if ($block->name()) {
                    $added_already = $this->getBlock($block->name());
                    if (!$added_already) {
                        $this->addBlock($block);
                    } else {
                        return $added_already;
                    }
                }
                return $block;
            } else {
                return new Core_Block($block_class);
            }
        }
        return new Core_Block();
    }

    /**
     * Returns a previously added block.
     *
     * If a block by with the name $name exists in the view, then it will return the block's instance.
     *
     * @param string $name
     * @return Core_Block|null
     */
    public function getBlock($name = "")
    {
        if (is_string($name) && !empty($name) && array_key_exists($name, $this->_blocks)) {
            return $this->_blocks[$name];
        }
        return null;
    }

    /**
     * Returns the html output of a child event.
     *
     * In order of when blocks were attached, it will render the html output of each block in the event.
     *
     * @param string $child_event
     * @return string
     */
    public function getChildHtml($child_event)
    {
        $child_html = "";
        if (array_key_exists($child_event, $this->_child_tree)) {
            if (array_key_exists($child_event, $this->_blocks)) {
                $child_html .= $this->_blocks[$child_event]->html();
            }

            foreach ($this->_child_tree[$child_event] as $block) {
                $child_html .= $block->html();
            }
        }
        return $child_html;
    }

    /**
     * Returns the html output of the layout.
     *
     * If there is no layout set, a blank string will be returned.
     *
     * Otherwise, it will capture and return the output of the layout file.
     *
     * If the html compressor is turned on (turned on by default) then it will pass the html string to the Core_Compressor::compressHtml() method and return it's output.
     *
     * @return null|string
     */
    public function html()
    {
        if ($this->_layout === false) {
            return "";
        }
        ob_start();
        require($this->_layout);
        $html = ob_get_contents();
        ob_end_clean();
        if ($this->_compress) {
            $compressor = new Core_Compressor($html);
            return $compressor->compressHtml();
        }
        return $html;
    }

    /**
     * Renders the html output of the layout.
     *
     * Calls the Core_View::html() method and immediately echoes it out.
     *
     * Before the return, it will turn off the _rendered flag to remove it from outputting on class destruction.
     *
     * @return null
     */
    public function render()
    {
        echo $this->html();
        $this->_rendered = true;
        return null;
    }

    /**
     * Allows html output compression to be turned on and off whenever necessary
     *
     * @param bool $compress
     * @return Core_View
     */
    public function compressOutput($compress = true)
    {
        $this->_compress = (boolean)$compress;
        return $this;
    }

    /**
     * Sets the layout file to be used when calling the Core_View::html() method.
     *
     * @param $path
     * @return Core_View
     * @throws Exception_File
     */
    public function setLayout($path)
    {
        $this->_layout = $this->_findLayout($path);
        return $this;
    }

    /**
     * Allows render-on-destruction to be turned on/off.
     *
     * @param bool $flag
     * @return $this
     */
    public function autoRender($flag = true)
    {
        $this->_rendered = !(boolean)$flag;
        return $this;
    }

    /**
     * Finds the layout file inside the app/layouts directory.
     *
     * Layout file names can not contain any of these characters: ['/', '.', '_', '|', ':'] or the finder will mistake them as subdirectories.
     *
     * Layout files must have the extension '.phtml' to denote that it handles mainly html logic rather than data processing.
     *
     * @param string $path
     * @return bool|string
     * @throws Exception_File
     */
    protected function _findLayout($path)
    {
        if (is_string($path)) {
            $location = App::getInstance()->normalizePath("layouts", $path, ".phtml");
            if ($location !== false) {
                return $location;
            }
            throw new Exception_File("Could not find layout file located at layouts" . DS . $path);
        }
        return false;
    }

    /**
     * Finds the block file inside the app/blocks directory.
     *
     * Block file names can not contain any of these characters: ['/', '.', '_', '|', ':'] or the finder will mistake them as subdirectories.
     *
     * Block files must have the extension '.php' to denote that it handles mainly data-processing rather than html logic.
     *
     * @param string $path
     * @return bool|string
     * @throws Exception_File
     */
    protected function _findBlock($path)
    {
        $path = App::getInstance()->normalizePath("blocks", $path);
        return $path !== false ? $path : false;
    }

    /**
     * Core_View destructor.
     *
     * If the layout has not been rendered yet, then it will do so.
     */
    public function __destruct()
    {
        if (!$this->_rendered) {
            $this->render();
        }
    }
}