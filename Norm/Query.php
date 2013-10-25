<?php

namespace simpleframework\Norm;

require_once ROOT . '/vendor/simpleframework/Norm/Adapter/Driver/Mysqli/Mysqli.php';
require_once ROOT . '/vendor/simpleframework/Norm/Metadata.php';
require_once ROOT . '/vendor/simpleframework/Norm/Observer/Subject.php';


class Query implements \Iterator, \Countable, Observer\Subject
{

    const TYPE_SELECT = 'select';
    const TYPE_INSERT = 'insert';
    const TYPE_DELETE = 'delete';
    const TYPE_UPDATE = 'update';

    protected static $_connections = array();
    public $_query = array(
        'select' => array(),
        'where'  => array(),
        'order'  => array(),
        'group'  => array(),
        'having' => array(),
        'join' => array(
            'inner' => array(),
            'left'  => array(),
            'right' => array(),
        ),
        'value' => array(),
        'set'   => array()
    );

    protected $_config       = null;
    protected $_numberRows   = null;
    protected $_targets      = array();
    protected $_metadata     = null;
    protected $_database     = null;
    protected $_stmtData     = null;
    protected $_stmtReturn   = null;
    protected $_stmtResult   = null;
    protected $_stmtPosition = null;
    protected $_stmtRow      = null;
    protected $_observers    = array();


    /** Observer **/

    public function getObservers()
    {

        return $this->_observers;

    }


    public function attach(Observer\Observer $observer)
    {

        $this->_observers[] = $observer;

    }


    public function detach(Observer\Observer $observer)
    {

        $key = array_search($observer, $this->_observers);
        unset($this->_observers[$key]);

    }


    public function notify($data)
    {

        foreach($this->_observers as $observer) {
            $observer->update($data);
        }

    }


    public function __construct($connection='default')
    {

        $this->_connection = $connection;

        return $this;

    }


    protected function _connect()
    {

        if (isset(self::$_connections[$this->_connection]) === false) {
            $config = $this->getConfig();

            $database = $this->getDatabase()->connect(
                            $config[$this->_connection]['hostname'],
                            $config[$this->_connection]['username'],
                            $config[$this->_connection]['password'],
                            $config[$this->_connection]['database']);

            $database->query("SET NAMES 'utf8'");
            self::$_connections[$this->_connection] = $database;
        }

    }


    public function setConfig(array $config)
    {

        $this->_config = $config;

    }


    public function getConfig()
    {

        if ($this->_config === null) {
            $this->_config = \simpleframework\Kernel::getConfig('db');
        }

        return $this->_config;

    }


    public function setMetadata(Adapter\Metadata $metadata)
    {

        $this->_metadata = $metadata;

    }


    public function getMetadata()
    {

        if ($this->_metadata === null) {
            $this->_metadata = Metadata::getInstance();
        }

        return $this->_metadata;

    }


    public function setDatabase(Adapter\Database $database)
    {

        $this->_database = $database;

    }


    public function getDatabase()
    {

        if ($this->_database === null) {
            $this->_database = new Adapter\Driver\Mysqli\Mysqli();
        }

        return $this->_database;

    }


    public function getType()
    {

        return $this->_query['type'];

    }


    public function getTarget()
    {

        return $this->_query['target'];

    }


    public function getSelect()
    {

        return $this->_query['select'];

    }


    public function from($table)
    {

        $this->_query['type'] = $this::TYPE_SELECT;
        $this->_query['target'] = $table;
        return $this;

    }


    public function insert($table)
    {

        $this->_query['type'] = $this::TYPE_INSERT;
        $this->_query['target'] = $table;
        return $this;

    }


    public function update($table)
    {

        $this->_query['type'] = $this::TYPE_UPDATE;
        $this->_query['target'] = $table;
        return $this;

    }


    public function delete($table)
    {

        $this->_query['type'] = $this::TYPE_DELETE;
        $this->_query['target'] = $table;
        return $this;

    }


    public function select($select, $append=true)
    {

        if ($append === false) {
            $this->_query['select'] = array();
        }

        $this->_query['select'][] = $select;
        return $this;

    }


    public function set(array $data)
    {

        $this->_query['set']   = array_merge($this->_query['set'], $data);
        return $this;

    }


    public function where($statement, $value=array(), $append=true)
    {

        if ($append === false) {
            $this->_query['where'] = array();
            $this->_query['value'] = array();
        }

        // Single value, found the placeholder and transform the value to placeholder => value
        if (is_array($value) === false) {
            preg_match('/(:[^ ]+)/', $statement, $result);

            if (count($result) == 0) {
                throw new \Exception('Can\'t find a placeholder to bind ' . $value, 1);
            }

            $value = array($result[1] => $value);
        }

        $this->_query['where'][] = $statement;
        $this->_query['value']   = array_merge($this->_query['value'], $value);
        return $this;

    }


    public function order($order, $append=true)
    {

        if ($append === false) {
            $this->_query['order'] = array();
        }

        $this->_query['order'][] = $order;
        return $this;

    }


    public function group($group, $append=true)
    {

        if ($append === false) {
            $this->_query['group'] = array();
        }

        $this->_query['group'][] = $group;
        return $this;

    }


    public function having($having, $append=true)
    {

        if ($append === false) {
            $this->_query['having'] = array();
        }

        $this->_query['having'][] = $having;
        return $this;

    }


    public function limit($from, $to)
    {

        $this->_query['limit'] = array($from, $to);
        return $this;

    }


    public function innerJoin($table, $condition)
    {

        $this->_query['join']['inner'][$table] = $condition;
        return $this;

    }


    public function leftJoin($table, $condition)
    {

        $this->_query['join']['left'][$table] = $condition;
        return $this;

    }


    public function rightJoin($table, $condition)
    {

        $this->_query['join']['right'][$table] = $condition;
        return $this;

    }


    public function getSql($skipFoundRows=false)
    {

        $this->_targets = array();

        switch ($this->_query['type']) {
            case self::TYPE_DELETE:
                $sql = 'DELETE FROM ' . $this->_query['target'];

                if (count($this->_query['where']) > 0) {
                    $sql .= ' WHERE (' . implode(') AND (', $this->_query['where']) . ')';
                }

            break;

            case self::TYPE_INSERT:
                $sql = 'INSERT INTO ' . $this->_query['target'];

                if (count($this->_query['set']) === 0) {
                    return null;
                }

                $names = array();
                $values = array();

                $this->_connect();

                foreach($this->_query['set'] as $key => $value) {
                    if (is_string($value) === true) {
                        $value = '\'' . self::$_connections[$this->_connection]->escape($value) . '\'';
                    }

                    $values[] = $value;
                    $names[] = $key;
                }

                $sql .= ' (' . implode(', ', $names) . ') VALUES (' . implode(', ', $values) . ')';

            break;

            case self::TYPE_UPDATE:
                $sql = 'UPDATE ' . $this->_query['target'];

                if (count($this->_query['set']) === 0) {
                    return null;
                }

                $columns = array();
                $this->_connect();

                foreach($this->_query['set'] as $key => $value) {
                    if (is_string($value) === true) {
                        $value = '\'' . self::$_connections[$this->_connection]->escape($value) . '\'';
                    }

                    $columns[] = $key . ' = ' . $value;
                }

                $sql .= ' SET ' . implode(', ', $columns);

                if (count($this->_query['where']) > 0) {
                    $sql .= ' WHERE (' . implode(') AND (', $this->_query['where']) . ')';
                }

            break;


            case self::TYPE_SELECT:
                $targets = array();

                $sql = ' FROM ';
                $sql .= $this->_query['target'];

                $this->_targets[] = $this->_query['target'];

                foreach ($this->_query['join']['inner'] as $table => $condition) {
                    $sql .= ' INNER JOIN ' . $table . ' ON (' . $condition . ')';
                    $this->_targets[] = $table;
                }

                foreach ($this->_query['join']['left'] as $table => $condition) {
                    $sql .= ' LEFT JOIN ' . $table . ' ON (' . $condition . ')';
                    $this->_targets[] = $table;
                }

                foreach ($this->_query['join']['right'] as $table => $condition) {
                    $sql .= ' RIGHT JOIN ' . $table . ' ON (' . $condition . ')';
                    $this->_targets[] = $table;
                }

                if (count($this->_query['where']) > 0) {
                    $sql .= ' WHERE (' . implode(') AND (', $this->_query['where']) . ')';
                }

                if (count($this->_query['group']) > 0) {
                    $sql .= ' GROUP BY ' . implode(', ', $this->_query['group']);
                }

                if (count($this->_query['having']) > 0) {
                    $sql .= ' HAVING (' . implode(') AND (', $this->_query['having']) . ')';
                }

                if (count($this->_query['order']) > 0) {
                    $sql .= ' ORDER BY ' . implode(', ', $this->_query['order']);
                }

                if (isset($this->_query['limit']) === true) {
                    $sql .= ' LIMIT ' . $this->_query['limit'][1] . ' OFFSET ' . $this->_query['limit'][0];
                }

                if (count($this->_query['select']) === 0) {
                    $sql = ' * ' . $sql;
                } else {
                    $sql = ' ' . implode(', ', $this->_query['select']) . $sql;
                }

                if ($skipFoundRows === false) {
                    $sql = 'SELECT SQL_CALC_FOUND_ROWS' . $sql;
                } else {
                    $sql = 'SELECT' . $sql;
                }
            break;

        }


        return $sql;

    }


    public function getValues()
    {

        return $this->_query['value'];

    }


    public function first()
    {

        $result = $this->execute();
        $this->_stmtData->close();

        if ($result === null) {
            return null;
        }

        $data = $this->_processRow($result);

        $object = $this->getMetadata()->mapToObjects($data, $this->_targets);

        return $object;

    }


    protected function _processRow(Adapter\DatabaseResult $result)
    {

        $data   = $result->fetchArray();
        $fields = $result->fetchFields();

        if ($fields === null) {
            return null;
        }

        foreach($fields as $i => &$field) {
            $field->value = $data[$i];
        }

        return $fields;

    }


    public function execute()
    {

        if ($this->_stmtData === null) {
            $this->_stmtReturn = $this->_execute();
        }

        if ($this->_stmtReturn === false) {
            return false;
        }

        switch ($this->_query['type']) {
            case self::TYPE_UPDATE:
            case self::TYPE_DELETE:
                $result = $this->_stmtData->getAffectedRows();
                break;
            case self::TYPE_INSERT:
                $result = self::$_connections[$this->_connection]->getInsertId();
                break;
            case self::TYPE_SELECT;
                if ($this->_stmtResult === null) {
                    $result = $this->_stmtData->getResult();
                    $this->_stmtResult = $result;
                } else {
                    $result = $this->_stmtResult;
                }
                $this->_count();
                break;
        }

        return $result;

    }


    public function _execute()
    {

        $sql = $this->getSql();

        if ($sql == null) {
            throw new \Exception('Query is empty');
        }

        preg_match_all('/:([a-zA-Z_-]+)/', $sql, $names);
        $sql = preg_replace('/:([a-zA-Z_-]+)/', '?', $sql);
        $names = $names[1];

        $types  = "";
        $values = array();
        $data   = $this->getValues();

        foreach($names as $name) {

            switch(gettype($data[':' . $name])) {
                case "integer":
                    $types .= "i";
                break;

                case "double":
                    $types .= "f";
                break;

                default:
                    $types .= "s";
            }

            $values[] = $data[':' . $name];

        }


        if ($types !== "") {
            array_unshift($values, $types);
        }

        $this->_connect();

        $connection = self::$_connections[$this->_connection];
        $this->_stmtData = $connection->prepare($sql);

        if ($types !== "") {
            $this->_stmtData->bindParams($values);
        }

        $start = microtime(true);

        if ($this->_stmtData !== false) {
            $this->_stmtReturn = $this->_stmtData->execute();
        }

        $this->notify(array(
            'duration' => microtime(true) - $start,
            'sql'      => $sql,
            'params'   => $this->getValues(),
            'error'    => array(
                'state'   => $connection->getSqlstate(),
                'code'    => $connection->getErrorNo(),
                'message' => $connection->getErrorMessage()
            )
        ));

        if ($this->_stmtData == false) {
            throw new \Exception($connection->getErrorMessage(), $connection->getErrorNo());
        }


        return $this->_stmtReturn;

    }


    public static function parseTableName($table)
    {

        preg_match('/([^ ]+)(?: as)?(?: ([^ ]+))?/i', $table, $parsed);

        if (count($parsed) === 0) {
            return null;
        }

        $table = $parsed[1];

        if (isset($parsed[2]) === false) {
            $alias = $table;
        } else {
            $alias = $parsed[2];
        }

        return array($table, $alias);

    }


    /** iterator **/

    public function rewind()
    {

        $this->execute();
        $this->_stmtResult->dataSeek(0);
        $this->_stmtPosition = 0;

    }


    public function valid()
    {

        if ($this->_stmtResult->fetchArray() !== null) {
            return true;
        }

        return false;

    }


    public function key()
    {

        return $this->_stmtPosition;

    }


    public function current()
    {

        $data = $this->_processRow($this->_stmtResult);
        $object = $this->getMetadata()->mapToObjects($data, $this->_targets);

        return $object;

    }


    public function next()
    {

        $this->_stmtPosition++;

    }


    /** countable **/

    public function count()
    {

        // No result ? The query should be executed to use FOUND_ROWS()
        if ($this->_stmtResult === null) {
            $this->execute();
        }

        return $this->_count();

    }


    public function _count()
    {

        if ($this->_numberRows !== null) {
            return $this->_numberRows;
        }

        $this->_connect();

        $sql = 'SELECT FOUND_ROWS()';
        $connection = self::$_connections[$this->_connection];
        $stmt = $connection->prepare($sql);

        $start = microtime(true);
        $stmt->execute();

        $this->notify(array(
            'duration' => microtime(true) - $start,
            'sql'      => $sql,
            'params'   => $this->getValues(),
            'error'    => array(
                'state'   => $connection->getSqlstate(),
                'code'    => $connection->getErrorNo(),
                'message' => $connection->getErrorMessage()
            )
        ));

        $result = $stmt->getResult();
        $stmt->close();

        if ($result === null) {
            return 0;
        }

        $data = $this->_processRow($result);

        if ($data === null) {
            return 0;
        }

        $this->_numberRows = (int) $data[0]->value;

        return $this->_numberRows;

    }


}