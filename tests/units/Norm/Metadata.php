<?php

namespace simpleframework\Norm\tests\units;

define('ROOT', realpath(getcwd() . DIRECTORY_SEPARATOR . ".."));

require_once ROOT . '/vendor/mageekguy.atoum.phar';
require_once ROOT . '/vendor/simpleframework/Norm/Query.php';
require_once ROOT . '/vendor/simpleframework/Norm/Model.php';
require_once ROOT . '/vendor/simpleframework/Norm/Metadata.php';
require_once ROOT . '/vendor/simpleframework/tests/Autoloader.php';


use mageekguy\atoum;

class Metadata extends atoum\test
{


    public function beforeTestMethod($method)
    {

        \simpleframework\tests\Autoloader::register();

    }


    public function testGetTable()
    {

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($table = $metadata->getTable('Match'))
            ->then
                ->string($table)
                ->isIdenticalTo('T_MATCH_MAT');

    }


    public function testGetTableNotFound()
    {

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($table = $metadata->getTable('NotFound'))
            ->then
                ->variable($table)
                ->isNull();

    }


    public function testGetPrimary()
    {

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($primary = $metadata->getPrimary('T_MATCH_MAT'))
            ->then
                ->array($primary)
                ->isIdenticalTo(array(
                    'key' => 'mat_id',
                    'params' => array(
                        'primary' => true,
                        'type' => 'int',
                        'name' => '_id'
                       )
                ));

    }


    public function testGetPrimaryNotFound()
    {

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($primary = $metadata->getPrimary('T_TABLE_NO_PRIMARY_TNP'))
            ->then
                ->variable($primary)
                ->isNull();

    }


    public function testGetPrimaryTableNotFound()
    {

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($primary = $metadata->getPrimary('NotFound'))
            ->then
                ->variable($primary)
                ->isNull();

    }


    public function testGetClass()
    {

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($class = $metadata->getClass('T_MATCH_MAT'))
            ->then
                ->string($class)
                ->isIdenticalTo('Match');

    }


    public function testGetClassNotFound()
    {

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($class = $metadata->getClass('T_NOT_FOUND_NFO'))
            ->then
                ->variable($class)
                ->isNull();

    }


    public function testGetColumns()
    {

        $columnsRef = array(
            'tea_id' => array(
                'primary' => true,
                'type' => 'int',
                'name' => '_id'
            ),
            'tea_name' => array(
                'type' => 'string',
                'name' => '_name'
            ),
            'tea_alias' => array(
                'type' => 'string',
                'name' => '_alias'
            )
        );

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($columns = $metadata->getColumns('T_TEAM_TEA'))
            ->then
                ->array($columns)
                ->isIdenticalTo($columnsRef);

    }


    public function testGetColumnsTableNotFound()
    {

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($columns = $metadata->getColumns('T_NOT_FOUND_NFO'))
            ->then
                ->array($columns)
                ->isIdenticalTo(array());

    }


    public function testGetColumnsTableNoColumns()
    {

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($columns = $metadata->getColumns('T_TABLE_NO_COLUMNS_TNC'))
            ->then
                ->array($columns)
                ->isIdenticalTo(array());

    }


    public function testGetColumnByName()
    {

        $columnRef = array(
            'type' => 'string',
            'name' => '_name'
        );

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($column = $metadata->getColumnByName('T_TEAM_TEA', '_name'))
            ->then
                ->array($column)
                ->isIdenticalTo($columnRef);

    }


    public function testGetColumnByNameTableNotFound()
    {

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($column = $metadata->getColumnByName('T_NOT_FOUND_NFO', '_name'))
            ->then
                ->variable($column)
                ->isNull();

    }


    public function testGetColumnByNameTableNoColumns()
    {

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($column = $metadata->getColumnByName('T_TABLE_NO_COLUMNS_TNC', '_name'))
            ->then
                ->variable($column)
                ->isNull();

    }


    public function testGetColumnByNameColumnNotFound()
    {

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($column = $metadata->getColumnByName('T_TEAM_TEA', '_notFound'))
            ->then
                ->variable($column)
                ->isNull();

    }


    public function testGetColumnByKey()
    {

        $columnRef = array(
            'type' => 'string',
            'name' => '_name'
        );

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($column = $metadata->getColumnByKey('T_TEAM_TEA', 'tea_name'))
            ->then
                ->array($column)
                ->isIdenticalTo($columnRef);

    }


    public function testGetColumnByKeyTableNotFound()
    {

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($column = $metadata->getColumnByKey('T_NOT_FOUND_NFO', 'tea_name'))
            ->then
                ->variable($column)
                ->isNull();

    }


    public function testGetColumnByKeyTableNoColumns()
    {

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($column = $metadata->getColumnByKey('T_TABLE_NO_COLUMNS_TNC', '_name'))
            ->then
                ->variable($column)
                ->isNull();

    }


    public function testGetColumnByKeyColumnNotFound()
    {

        $this
            ->if($metadata = \simpleframework\Norm\Metadata::getInstance('/vendor/simpleframework/tests/model/*.php'))
            ->if($column = $metadata->getColumnByKey('T_TEAM_TEA', '_notFound'))
            ->then
                ->variable($column)
                ->isNull();

    }


}