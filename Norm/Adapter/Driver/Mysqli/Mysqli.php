<?php

namespace simpleframework\Norm\Adapter\Driver\Mysqli;

require_once ROOT . '/vendor/simpleframework/Norm/Adapter/Database.php';
require_once ROOT . '/vendor/simpleframework/Norm/Adapter/Driver/Mysqli/Statement.php';


class Mysqli implements \simpleframework\Norm\Adapter\Database
{


    protected $_connection;


    public function connect($hostname, $username, $password, $database)
    {

        $this->_connection = new \mysqli($hostname, $username, $password, $database);
        return $this;

    }


    public function query($sql)
    {

        $this->_connection->query($sql);

    }


    public function prepare($sql)
    {

        $statement = new Statement($this->_connection->prepare($sql));
        return $statement;

    }


    public function getInsertId()
    {

        return $this->_connection->insert_id;

    }


    public function getSqlState()
    {

        return $this->_connection->sqlstate;

    }


    public function getErrorNo()
    {

        return $this->_connection->errno;

    }


    public function getErrorMessage()
    {

        return $this->_connection->error;

    }


}