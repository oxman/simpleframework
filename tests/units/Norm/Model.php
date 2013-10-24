<?php

namespace simpleframework\Norm\tests\units;

define('ROOT', realpath(getcwd() . DIRECTORY_SEPARATOR . ".."));

require_once ROOT . '/vendor/mageekguy.atoum.phar';
require_once ROOT . '/vendor/simpleframework/Norm/Observer/Observer.php';
require_once ROOT . '/vendor/simpleframework/Norm/Adapter/Driver/Mysqli/Mysqli.php';
require_once ROOT . '/vendor/simpleframework/Norm/Query.php';
require_once ROOT . '/vendor/simpleframework/Norm/Model.php';
require_once ROOT . '/vendor/simpleframework/Norm/Metadata.php';
require_once ROOT . '/vendor/simpleframework/tests/Autoloader.php';


use mageekguy\atoum;

class Model extends atoum\test
{


    public function beforeTestMethod($method)
    {

        \simpleframework\tests\Autoloader::register();

    }


    public function testSave()
    {

        $this
            ->if($team = new \Team)
            ->and($team->setName('Bouh'))
            ->and($result = $team->save())
            ->then
                ->boolean($result)
                ->isIdenticalTo(true);

    }


}