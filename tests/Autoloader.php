<?php

namespace simpleframework\tests;

class Autoloader
{


    static public function register()
    {

        spl_autoload_register('\simpleframework\tests\Autoloader::autoload');

    }


    static public function autoload($class)
    {

        if (file_exists($file = ROOT . '/vendor/simpleframework/tests/model/' . $class . '.php') === true) {
            include_once $file;
        }

    }


}