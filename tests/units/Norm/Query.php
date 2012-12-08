<?php

namespace simpleframework\Norm\tests\units;

define('ROOT', realpath(getcwd() . DIRECTORY_SEPARATOR . ".."));

require_once ROOT . '/vendor/mageekguy.atoum.phar';
require_once ROOT . '/vendor/simpleframework/Norm/Observer/Observer.php';
require_once ROOT . '/vendor/simpleframework/Norm/Adapter/Driver/Mysqli/Mysqli.php';
require_once ROOT . '/vendor/simpleframework/Norm/Metadata.php';
require_once ROOT . '/vendor/simpleframework/Norm/Model.php';
require_once ROOT . '/vendor/simpleframework/Norm/Query.php';
require_once ROOT . '/vendor/simpleframework/tests/model/Match.php';
require_once ROOT . '/vendor/simpleframework/tests/model/Team.php';

use mageekguy\atoum;

class Query extends atoum\test
{


    public function testFrom()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->from("test"))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($target = $q->getTarget())
            ->then
                ->string($target)
                ->isIdenticalTo('test')
            ->if($type = $q->getType())
            ->then
                ->string($type)
                ->isIdenticalTo($q::TYPE_SELECT);

    }


    public function testDelete()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->delete("test"))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($target = $q->getTarget())
            ->then
                ->string($target)
                ->isIdenticalTo('test')
            ->if($type = $q->getType())
            ->then
                ->string($type)
                ->isIdenticalTo($q::TYPE_DELETE);

    }


    public function testUpdate()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->update("test"))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($target = $q->getTarget())
            ->then
                ->string($target)
                ->isIdenticalTo('test')
            ->if($type = $q->getType())
            ->then
                ->string($type)
                ->isIdenticalTo($q::TYPE_UPDATE);

    }


    public function testInsert()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->insert("test"))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($target = $q->getTarget())
            ->then
                ->string($target)
                ->isIdenticalTo('test')
            ->if($type = $q->getType())
            ->then
                ->string($type)
                ->isIdenticalTo($q::TYPE_INSERT);

    }


    public function testSelect()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->select("zim.a, boum as truc, vlan"))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($selects = $q->getSelect())
            ->then
                ->array($selects)
                    ->isIdenticalTo(array('zim.a, boum as truc, vlan'))
            ->if($q = $q->select("boum", false))
            ->if($selects = $q->getSelect())
            ->then
                ->array($selects)
                    ->isIdenticalTo(array('boum'));

    }


    public function testParseTableName()
    {

        $this
            ->if($result = \simpleframework\Norm\Query::parseTableName('T_BOUH_BOU'))
            ->then
                ->array($result)
                ->hasSize(2)
                ->string($result[0])
                    ->isIdenticalTo('T_BOUH_BOU')
                ->string($result[1])
                    ->isIdenticalTo('T_BOUH_BOU');

        $this
            ->if($result = \simpleframework\Norm\Query::parseTableName('T_BOUH_BOU as Bou'))
            ->then
                ->array($result)
                ->hasSize(2)
                ->string($result[0])
                    ->isIdenticalTo('T_BOUH_BOU')
                ->string($result[1])
                    ->isIdenticalTo('Bou');

        $this
            ->if($result = \simpleframework\Norm\Query::parseTableName('T_BOUH_BOU Bou'))
            ->then
                ->array($result)
                ->hasSize(2)
                ->string($result[0])
                    ->isIdenticalTo('T_BOUH_BOU')
                ->string($result[1])
                    ->isIdenticalTo('Bou');

        $this
            ->if($result = \simpleframework\Norm\Query::parseTableName('T_BOUH_BOU AS Bou'))
            ->then
                ->array($result)
                ->hasSize(2)
                ->string($result[0])
                    ->isIdenticalTo('T_BOUH_BOU')
                ->string($result[1])
                    ->isIdenticalTo('Bou');

    }


    public function testSelectFromWhere()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->select('bouh')->from('T_MATCH_MAT')->where(':id', array(':a' => 'bouh')))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($sql = $q->getSql())
            ->then
                ->string($sql)
                    ->isIdenticalTo('SELECT SQL_CALC_FOUND_ROWS bouh FROM T_MATCH_MAT WHERE (:id)');

    }


    public function testWhereSingleValue()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->select('bouh')->from('T_MATCH_MAT')->where(':id', 'bouh'))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($sql = $q->getSql())
            ->then
                ->string($sql)
                    ->isIdenticalTo('SELECT SQL_CALC_FOUND_ROWS bouh FROM T_MATCH_MAT WHERE (:id)');

    }


    public function testWhereAppend()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->select('bouh')->from('T_MATCH_MAT')->where(':id', 'bouh'))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($sql = $q->where(':truc', 'choum', false)->getSql())
            ->then
                ->string($sql)
                    ->isIdenticalTo('SELECT SQL_CALC_FOUND_ROWS bouh FROM T_MATCH_MAT WHERE (:truc)');

    }


    public function testWhereException()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->select('bouh')->from('T_MATCH_MAT'))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->exception(function() use ($q) {
                    $q->where('id', 'bouh');
                })
                ->hasCode(1)
                ->hasMessage('Can\'t find a placeholder to bind bouh');

    }


    public function testGroup()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->select('bouh')->from('T_MATCH_MAT')->group('zoom'))
            ->if($sql = $q->getSql())
            ->then
                ->string($sql)
                    ->isIdenticalTo('SELECT SQL_CALC_FOUND_ROWS bouh FROM T_MATCH_MAT GROUP BY zoom')
            ->if($q = $q->group('ziom', false))
            ->if($sql = $q->getSql())
            ->then
                ->string($sql)
                    ->isIdenticalTo('SELECT SQL_CALC_FOUND_ROWS bouh FROM T_MATCH_MAT GROUP BY ziom');

    }


    public function testHaving()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->select('bouh')->from('T_MATCH_MAT')->having('zoom'))
            ->if($sql = $q->getSql())
            ->then
                ->string($sql)
                    ->isIdenticalTo('SELECT SQL_CALC_FOUND_ROWS bouh FROM T_MATCH_MAT HAVING (zoom)')
            ->if($q = $q->having('ziom', false))
            ->if($sql = $q->getSql())
            ->then
                ->string($sql)
                    ->isIdenticalTo('SELECT SQL_CALC_FOUND_ROWS bouh FROM T_MATCH_MAT HAVING (ziom)');

    }


    public function testLimit()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->select('bouh')->from('T_MATCH_MAT')->limit(8, 3))
            ->if($sql = $q->getSql())
            ->then
                ->string($sql)
                    ->isIdenticalTo('SELECT SQL_CALC_FOUND_ROWS bouh FROM T_MATCH_MAT LIMIT 3 OFFSET 8');

    }


    public function testOrder()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->select('bouh')->from('T_MATCH_MAT')->order('zoom'))
            ->if($sql = $q->getSql())
            ->then
                ->string($sql)
                    ->isIdenticalTo('SELECT SQL_CALC_FOUND_ROWS bouh FROM T_MATCH_MAT ORDER BY zoom')
            ->if($q = $q->order('ziom', false))
            ->if($sql = $q->getSql())
            ->then
                ->string($sql)
                    ->isIdenticalTo('SELECT SQL_CALC_FOUND_ROWS bouh FROM T_MATCH_MAT ORDER BY ziom');

    }


    public function testInnerJoin()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->select('bouh')->from('T_MATCH_MAT m')->innerJoin('Pouf', 'Pouf.a = m.id'))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($sql = $q->getSql())
            ->then
                ->string($sql)
                    ->isIdenticalTo('SELECT SQL_CALC_FOUND_ROWS bouh FROM T_MATCH_MAT m INNER JOIN Pouf ON (Pouf.a = m.id)');

    }


    public function testLeftJoin()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->select('bouh')->from('T_MATCH_MAT m')->leftJoin('Pouf', 'Pouf.a = m.id'))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($sql = $q->getSql())
            ->then
                ->string($sql)
                    ->isIdenticalTo('SELECT SQL_CALC_FOUND_ROWS bouh FROM T_MATCH_MAT m LEFT JOIN Pouf ON (Pouf.a = m.id)');

    }


    public function testRightJoin()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->select('bouh')->from('T_MATCH_MAT m')->rightJoin('Pouf', 'Pouf.a = m.id'))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($sql = $q->getSql())
            ->then
                ->string($sql)
                    ->isIdenticalTo('SELECT SQL_CALC_FOUND_ROWS bouh FROM T_MATCH_MAT m RIGHT JOIN Pouf ON (Pouf.a = m.id)');

    }


    public function testSkipFoundRow()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->select('bouh')->from('T_MATCH_MAT m'))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($sql = $q->getSql(true))
            ->then
                ->string($sql)
                    ->isIdenticalTo('SELECT bouh FROM T_MATCH_MAT m');


    }


    public function testGetValues()
    {

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array()))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($q = $q->select('bouh')->from('T_MATCH_MAT')->where(':id', 'bouh'))
            ->then
                ->object($q)
                ->isInstanceOf('\simpleframework\Norm\Query')
            ->if($values = $q->getValues())
            ->then
                ->array($values)
                    ->isIdenticalTo(array(':id' => 'bouh'));

    }


    public function testFirst()
    {

        $this->mockGenerator->generate('\simpleframework\Norm\Adapter\Database', '\DatabaseMock');
        $this->mockGenerator->generate('\simpleframework\Norm\Adapter\DatabaseStatement', '\DatabaseStatementMock');
        $this->mockGenerator->generate('\simpleframework\Norm\Adapter\DatabaseResult', '\DatabaseResultMock');

        $databaseMock = new \DatabaseMock\Database();
        $databaseStatementMock = new \DatabaseStatementMock\DatabaseStatement();
        $databaseResultMock = new \DatabaseResultMock\DatabaseResult();

        $databaseMock->getMockController()->connect = function() use($databaseMock) {
            return $databaseMock;
        };

        $databaseMock->getMockController()->prepare = function() use($databaseStatementMock) {
            return $databaseStatementMock;
        };

        $databaseStatementMock->getMockController()->execute = function() use($databaseStatementMock) {
            return $databaseStatementMock;
        };

        $databaseStatementMock->getMockController()->getResult = function() use($databaseResultMock) {
            return $databaseResultMock;
        };

        $databaseResultMock->getMockController()->fetchArray = function() {
            return array(3, 'Olympique de Marseille', 'OM');
        };

        $databaseResultMock->getMockController()->fetchFields = function() {
            $fields = array();

            $stdClass = new \stdClass();
            $stdClass->name    = 'tea_id';
            $stdClass->orgname = 'tea_id';
            $stdClass->table   = 'T_TEAM_TEA';
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name    = 'tea_name';
            $stdClass->orgname = 'tea_name';
            $stdClass->table   = 'T_TEAM_TEA';
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name    = 'tea_alias';
            $stdClass->orgname = 'tea_alias';
            $stdClass->table   = 'T_TEAM_TEA';
            $fields[] = $stdClass;

            return $fields;

        };

        $metadataMock = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php');

        $teamRef = new \Team();
        $teamRef->setId(3);
        $teamRef->setName('Olympique de Marseille');
        $teamRef->setAlias('OM');

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array('default' => array('hostname' => '', 'username' => '', 'password' => '', 'database' => ''))))
            ->if($q->setDatabase($databaseMock))
            ->if($q->setMetadata($metadataMock))
            ->if($q->from('T_TEAM_TEA'))
            ->if($team = $q->first())
            ->then
                ->object($team)
                    ->isCloneOf($teamRef);

    }


    public function testFirstNoresult()
    {

        $this->mockGenerator->generate('\simpleframework\Norm\Adapter\Database', '\DatabaseMock');
        $this->mockGenerator->generate('\simpleframework\Norm\Adapter\DatabaseStatement', '\DatabaseStatementMock');
        $this->mockGenerator->generate('\simpleframework\Norm\Adapter\DatabaseResult', '\DatabaseResultMock');

        $databaseMock = new \DatabaseMock\Database();
        $databaseStatementMock = new \DatabaseStatementMock\DatabaseStatement();
        $databaseResultMock = new \DatabaseResultMock\DatabaseResult();

        $databaseMock->getMockController()->connect = function() use($databaseMock) {
            return $databaseMock;
        };

        $databaseMock->getMockController()->prepare = function() use($databaseStatementMock) {
            return $databaseStatementMock;
        };

        $databaseStatementMock->getMockController()->execute = function() use($databaseStatementMock) {
            return $databaseStatementMock;
        };

        $databaseStatementMock->getMockController()->getResult = function() use($databaseResultMock) {
            return null;
        };

        $metadataMock = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php');

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array('default' => array('hostname' => '', 'username' => '', 'password' => '', 'database' => ''))))
            ->if($q->setDatabase($databaseMock))
            ->if($q->setMetadata($metadataMock))
            ->if($q->from('T_TEAM_TEA'))
            ->if($team = $q->first())
            ->then
                ->variable($team)
                    ->isNull();

    }


    public function testCount()
    {

        $this->mockGenerator->generate('\simpleframework\Norm\Adapter\Database', '\DatabaseMock');
        $this->mockGenerator->generate('\simpleframework\Norm\Adapter\DatabaseStatement', '\DatabaseStatementMock');
        $this->mockGenerator->generate('\simpleframework\Norm\Adapter\DatabaseResult', '\DatabaseResultMock');

        $databaseMock = new \DatabaseMock\Database();
        $databaseStatementMock = new \DatabaseStatementMock\DatabaseStatement();
        $databaseResultMock = new \DatabaseResultMock\DatabaseResult();

        $databaseMock->getMockController()->connect = function() use($databaseMock) {
            return $databaseMock;
        };

        $databaseMock->getMockController()->prepare = function() use($databaseStatementMock) {
            return $databaseStatementMock;
        };

        $databaseStatementMock->getMockController()->execute = function() use($databaseStatementMock) {
            return $databaseStatementMock;
        };

        $databaseStatementMock->getMockController()->getResult = function() use($databaseResultMock) {
            return $databaseResultMock;
        };

        $databaseResultMock->getMockController()->fetchArray = function() {
            return array(32);
        };

        $databaseResultMock->getMockController()->fetchFields = function() {
            $fields = array();

            $stdClass = new \stdClass();
            $stdClass->name    = 'count';
            $stdClass->orgname = 'count';
            $stdClass->table   = 'T_TEAM_TEA';
            $fields[] = $stdClass;

            return $fields;

        };

        $metadataMock = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php');

        $this
            ->if($q = new \simpleframework\Norm\Query('default', array('default' => array('hostname' => '', 'username' => '', 'password' => '', 'database' => ''))))
            ->if($q->setDatabase($databaseMock))
            ->if($q->setMetadata($metadataMock))
            ->if($q->from('T_TEAM_TEA'))
            ->if($count = $q->count())
            ->then
                ->variable($count)
                    ->isIdenticalTo(32);


    }


}