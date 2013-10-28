<?php

namespace simpleframework\Norm\tests\units;

define('ROOT', realpath(getcwd() . DIRECTORY_SEPARATOR . ".."));

require_once ROOT . '/vendor/mageekguy.atoum.phar';
require_once ROOT . '/vendor/simpleframework/Observer/Observer.php';
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

        \Match::setMetadata(\simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'));

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

        \Match::setMetadata(\simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'));

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
        $queryMock->getMockController()->first = function() {
            $match = new \Match;
            return $match;
        };

        $this->mockGenerator->generate('\simpleframework\Norm\Adapter\Database', '\DatabaseMock');
        $databaseMock = new \DatabaseMock\Database();
        $databaseMock->getMockController()->connect = $databaseMock;
        $databaseMock->getMockController()->escape = function($value) { return $value; };

        $queryMock->setConfig(array('default' => array('hostname' => '', 'username' => '', 'password' => '', 'database' => '')));
        $queryMock->setDatabase($databaseMock);

        \Match::setMetadata(\simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'));
        \Match::staticSetQuery($queryMock);

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
        $queryMock->getMockController()->first = function() {
            return array(3, 3, 3);
        };

        $this->mockGenerator->generate('\simpleframework\Norm\Adapter\Database', '\DatabaseMock');
        $databaseMock = new \DatabaseMock\Database();
        $databaseMock->getMockController()->connect = $databaseMock;
        $databaseMock->getMockController()->escape = function($value) { return $value; };

        $queryMock->setConfig(array('default' => array('hostname' => '', 'username' => '', 'password' => '', 'database' => '')));
        $queryMock->setDatabase($databaseMock);

        \Team::setMetadata(\simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'));
        \Team::setStaticQuery($queryMock);

        $this
            ->if($team = \Team::findByName("Test"))
            ->then
                ->object($team)
                ->isInstanceOf('\Iterator');

    }


    public function testSave()
    {

        $this->mockGenerator->generate('\simpleframework\Norm\Query', '\QueryMock');
        $queryMock = new \QueryMock\Query();
        $queryMock->getMockController()->execute = 3;

        $this->mockGenerator->generate('\simpleframework\Norm\Adapter\Database', '\DatabaseMock');
        $databaseMock = new \DatabaseMock\Database();
        $databaseMock->getMockController()->connect = $databaseMock;
        $databaseMock->getMockController()->escape = function($value) { return $value; };

        $queryMock->setConfig(array('default' => array('hostname' => '', 'username' => '', 'password' => '', 'database' => '')));
        $queryMock->setDatabase($databaseMock);

        \Team::setMetadata(\simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'));

        $this
            ->if($team = new \Team)
            ->and($team->setQuery($queryMock))
            ->and($team->setName('Bouh'))
            ->and($result = $team->save())
            ->and($sql = $queryMock->getSql())
            ->then
                ->string($sql)
                ->isIdenticalTo('INSERT INTO `T_TEAM_TEA` (tea_name) VALUES (\'Bouh\')')
            ->then
                ->boolean($result)
                ->isIdenticalTo(true)
            ->and($id = $team->getId())
            ->then
                ->integer($id)
                ->isIdenticalTo(3);

    }


    public function testUpdate()
    {

        $this->mockGenerator->generate('\simpleframework\Norm\Query', '\QueryMock');
        $queryMock = new \QueryMock\Query();
        $queryMock->getMockController()->execute = 3;

        $this->mockGenerator->generate('\simpleframework\Norm\Adapter\Database', '\DatabaseMock');
        $databaseMock = new \DatabaseMock\Database();
        $databaseMock->getMockController()->connect = $databaseMock;
        $databaseMock->getMockController()->escape = function($value) { return $value; };

        $queryMock->setConfig(array('default' => array('hostname' => '', 'username' => '', 'password' => '', 'database' => '')));
        $queryMock->setDatabase($databaseMock);

        \Team::setMetadata(\simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'));

        $this
            ->if($team = new \Team) // in standard code use Team::getById(1)
            ->and($team->setQuery($queryMock))
            ->and($team->setId(4))
            ->and($team->setName('Bouh'))
            ->and($result = $team->update())
            ->and($sql = $queryMock->getSql())
            ->then
                ->string($sql)
                ->isIdenticalTo('UPDATE `T_TEAM_TEA` SET tea_name = \'Bouh\' WHERE (tea_id = :tea_id)')
            ->then
                ->boolean($result)
                ->isIdenticalTo(true);

    }


}
