<?php

namespace simpleframework\Norm\Adapter\Driver\Mysqli;

require_once ROOT . '/vendor/simpleframework/Norm/Adapter/DatabaseStatement.php';
require_once ROOT . '/vendor/simpleframework/Norm/Adapter/Driver/Mysqli/Result.php';


class Statement implements \simpleframework\Norm\Adapter\DatabaseStatement
{


    protected $_statement;


    public function __construct(\mysqli_stmt $statement)
    {

        $this->_statement = $statement;

    }


    public function bindParams(array $params)
    {

        call_user_func_array(array($this->_statement, 'bind_param'), $params);

    }


    public function execute()
    {

        return $this->_statement->execute();

    }


    public function getAffectedRows()
    {

        return $this->_statement->affected_rows;

    }


    public function getResult()
    {

        $result = $this->_statement->get_result();

        if ($result === false) {
            return null;
        }

        return new Result($result);

    }


    public function close()
    {

        $this->_statement->close();

    }


}