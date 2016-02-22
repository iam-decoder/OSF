<?php

/**
 * Class Home_Controller
 */
class Home_Controller extends Core_Controller
{

    /**
     * Home_Controller constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function index($val1 = NULL, $val2 = NULL, $val3 = NULL, $val4 = NULL, $val5 = NULL)
    {
        var_dump("Value 1: " . $val1);
        var_dump("Value 2: " . $val2);
        var_dump("Value 3: " . $val3);
        var_dump("Value 4: " . $val4);
        var_dump("Value 5: " . $val5);
        App::getInstance()->view()->createBlock("homepage_content")->setTemplate('home.index')->addTo("content");
    }
}