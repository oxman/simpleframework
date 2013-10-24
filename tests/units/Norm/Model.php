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


    public function testToString()
    {

        $metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php');
        \simpleframework\Norm\ModelDependencyInjection::setMetadata($metadata);

        $this
            ->if($match = new \Match())
            ->and($match->setId(3))
            ->and($text = sprintf('%s', $match))
            ->then
                ->string($text)
                ->isIdenticalTo('Match#3');

    }


    public function testMagicCall()
    {

        $metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php');
        \simpleframework\Norm\ModelDependencyInjection::setMetadata($metadata);

        $this
            ->if($match = new \Match())
            ->and($match->setId(3))
            ->and($id = $match->getId())
            ->then
                ->integer($id)
                ->isIdenticalTo(3);

    }


    public function testMagicGetBy()
    {

        $this->mockGenerator->generate('\simpleframework\Norm\Query', '\QueryMock');
        $queryMock = new \QueryMock\Query();
        $metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php');

        \simpleframework\Norm\ModelDependencyInjection::setMetadata($metadata);
        \simpleframework\Norm\ModelDependencyInjection::setQuery($queryMock);

        $queryMock->getMockController()->first = function() {
            $match = new \Match;
            return $match;
        };

        $this
            ->if($match = \Match::getById(1))
            ->then
                ->object($match)
                ->isInstanceOf('\Match');

    }


    public function testMagicFindBy()
    {

        $this->mockGenerator->generate('\simpleframework\Norm\Query', '\QueryMock');
        $queryMock = new \QueryMock\Query();
        $metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php');

        \simpleframework\Norm\ModelDependencyInjection::setMetadata($metadata);
        \simpleframework\Norm\ModelDependencyInjection::setQuery($queryMock);

        $queryMock->getMockController()->first = function() {
            return array(3, 3, 3);
        };

        $this
            ->if($team = \Team::findByName("Test"))
            ->then
                ->object($team)
                ->isInstanceOf('\Iterator');

    }


    public function testSave()
    {

        $metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php');
        $this->mockGenerator->generate('\simpleframework\Norm\Query', '\QueryMock');
        $queryMock = new \QueryMock\Query();
        $queryMock->getMockController()->execute = 3;

        $this->mockGenerator->generate('\simpleframework\Norm\Adapter\Database', '\DatabaseMock');
        $databaseMock = new \DatabaseMock\Database();
        $databaseMock->getMockController()->connect = $databaseMock;
        $databaseMock->getMockController()->escape = function($value) { return $value; };

        $queryMock->setConfig(array('default' => array('hostname' => '', 'username' => '', 'password' => '', 'database' => '')));
        $queryMock->setDatabase($databaseMock);

        \simpleframework\Norm\ModelDependencyInjection::setMetadata($metadata);
        \simpleframework\Norm\ModelDependencyInjection::setQuery($queryMock);

        $this
            ->if($team = new \Team)
            ->and($team->setName('Bouh'))
            ->and($result = $team->save())
            ->and($sql = $queryMock->getSql())
            ->then
                ->string($sql)
                ->isIdenticalTo('INSERT INTO T_TEAM_TEA (tea_name) VALUES (\'Bouh\')')
            ->then
                ->boolean($result)
                ->isIdenticalTo(true)
            ->and($id = $team->getId())
            ->then
                ->integer($id)
                ->isIdenticalTo(3);

    }


}
