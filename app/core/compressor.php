<?php

/**
 * Class Core_Compressor
 *
 * @author Travis Neal
 */
class Core_Compressor
{
    protected

        /**
         * @var string
         */
        $_contents;

    /**
     * Core_Compressor constructor.
     * @param string $content sets the content to be compressed later
     */
    public function __construct($content)
    {
        $this->setSource($content);
    }

    /**
     * Sets the content to be compressed later.
     * @param string $source
     * @return Core_Compressor
     */
    public function setSource($source)
    {
        if (is_string($source)) {
            $this->_contents = $source;
        }
        return $this;
    }

    /**
     * Compresses an html string by removing all whitespace between tags
     *
     * @return null|string
     */
    public function compressHtml()
    {
        $this->_contents = $this->cleanWhitespace(">", "<");
        if (empty($this->_contents)) {
            return null;
        }
        return $this->_contents;
    }

    /**
     * Removes whitespace between 2 delimiters
     *
     * @param string $left delimiter to the left of the whitespace
     * @param string $right delimiter to the right of the whitespace
     * @return string
     */
    protected function cleanWhitespace($left, $right)
    {
        return preg_replace('/(' . $left . '\s+' . $right . ')/', $left . $right, $this->_contents);
    }
}