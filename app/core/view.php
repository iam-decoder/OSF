<?php

class Core_View
{
    protected
        $_child_tree = null,
        $_blocks = null;

    public function __construct()
    {
        $this->_child_tree = array();
        $this->_blocks = array();
    }

    public function attachChild($child, &$block)
    {
        if ($block instanceof Core_Block) {
            if (!array_key_exists($child, $this->_child_tree)) {
                $this->_child_tree[$child] = array($block);
            } elseif (!in_array($block, $this->_child_tree[$child])) {
                $this->_child_tree[$child][] = $block;
            }
        } elseif (is_string($block) && !empty($block)) {
            if (array_key_exists($block, $this->_blocks)) {
                $this->attachChild($child, $this->_blocks[$block]);
            }
        }
        return $this;
    }

    public function addBlock(&$block)
    {
        if ($block instanceof Core_Block) {
            if (!$block->name()) {
                throw new Exception_View("Blocks must be named in order to attach it to the view.");
            } elseif (array_key_exists($block->name(), $this->_blocks)) {
                throw new Exception_View("Block [{$block->name()}] already exists. Rename the block or attach as an anonymous block to the current one.");
            }
            $this->_blocks[$block->name()] = $block;
            $this->_child_tree[$block->name()] = array();
            return $block;
        }
        return false;
    }

    public function createBlock($block_class = null)
    {
        if (is_string($block_class)) {
            $block_path = $this->_findBlock(strtolower($block_class));
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
    }

    public function getBlock($name = "")
    {
        if (is_string($name) && !empty($name) && array_key_exists($name, $this->_blocks)) {
            return $this->_blocks[$name];
        }
        return null;
    }

    public function getChildHtml($child_name)
    {
        $child_html = "";
        if(array_key_exists($child_name, $this->_child_tree)) {
            if(array_key_exists($child_name, $this->_blocks)) {
                $child_html .= $this->_blocks[$child_name]->html();
            }

            foreach ($this->_child_tree[$child_name] as $block) {
                $child_html .= $block->html();
            }
        }
        return $child_html;
    }

    public function html()
    {
        if($this->_layout === false){
            return "";
        }
        ob_start();
        require($this->_layout);
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    public function render()
    {
        if($this->_layout !== false){
            require($this->_layout);
        }
    }

    public function setLayout($path)
    {
        $this->_layout = $this->_findLayout($path);
    }

    protected function _findLayout($path)
    {
        $location = App::getInstance()->normalizePath("layouts", $path, ".phtml");
        if ($location !== false) {
            return $location;
        }
        throw new Exception_File("Could not find layout file located at layouts" . DS . $path);
    }

    protected function _findBlock($path)
    {
        $path = App::getInstance()->normalizePath("block", $path);
        return $path !== false ? $path : false;
    }

    public function __destruct()
    {
        $this->render();
    }
}