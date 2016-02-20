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

    public function index()
    {
        App::getInstance()->view('home.index');
    }
}