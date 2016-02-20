<?php
return array(
    'default_controller' => "Home",
    'default_method' => "index",

    'rewrites' => array(
        '/homepage' => "Home@index",
        '/inde' => "Home",
        '/homepage/just/for/testing' => "Home@Test"
    )
);