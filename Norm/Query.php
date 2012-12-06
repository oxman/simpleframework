<?php

namespace simpleframework\Norm;

require_once ROOT . '/vendor/simpleframework/Norm/Metadata.php';
require_once ROOT . '/vendor/simpleframework/Norm/Observer/Subject.php';


class Query implements \Countable, Observer\Subject
{

    const TYPE_SELECT = 'select';
    const TYPE_INSERT = 'insert';
    const TYPE_DELETE = 'delete';
    const TYPE_UPDATE = 'update';

    protected static $_connections = array();
    protected $_query = array(
        'select' => array(),
        'where'  => array(
            'statement' => array(),
            'value'     => array()
        ),
        'order'  => array(),
        'group'  => array(),
        'having' => array(),
        'join' => array(
            'inner' => array(),
            'left'  => array(),
            'right' => array(),
        )
    );

    protected $_numberRows = null;
    protected $_targets    = array();
    protected $_metadata   = null;
    protected $_stmtData   = null;
    protected $_stmtResult = null;
    protected $_observers  = array();


    /** Observer **/

    public function attach(Observer\Observer $observer)
    {

      $this->_observers[] = $observer;

    }

    public function detach(Observer\Observer $observer)
    {

      $key = array_search($observer_in, $this->_observers);
      var_dump($key);

      foreach($this->observers as $okey => $oval) {
        if ($oval == $observer) {
          unset($this->_observers[$okey]);
        }
      }

    }


    public function notify($data)
    {

        foreach($this->_observers as $observer) {
            $observer->update($data);
        }

    }


    public function __construct($connection='default', $metadata=null, $config=null)
    {

        $this->_connection = $connection;

        if ($metadata === null) {
            $this->_metadata = ModelMeta2::getInstance();
        } else {
            $this->_metadata = $metadata;
        }

        if ($config === null) {
            $config = Kernel::getConfig('db');
        }

        if (isset(self::$_connections[$this->_connection]) === false) {
            $mysqli = new \mysqli($config[$this->_connection]['hostname'],
                                  $config[$this->_connection]['username'],
                                  $config[$this->_connection]['password'],
                                  $config[$this->_connection]['database']);

            $mysqli->query("SET NAMES 'utf8'");
            self::$_connections[$this->_connection] = $mysqli;
        }

        return $this;

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


    public function whereSql(Query $q)
    {


    }


    public function where($statement, $value=array(), $append=true)
    {

        if ($append === false) {
            $this->_query['where']['statement'] = array();
            $this->_query['where']['value'] = array();
        }

        // Single value, found the placeholder and transform the value to placeholder => value
        if (is_array($value) === false) {
            preg_match('/(:[^ ]+)/', $statement, $result);

            if (count($result) == 0) {
                throw new \Exception('Can\'t find a placeholder to bind ' . $value, 1);
            }

            $value = array($result[1] => $value);
        }

        $this->_query['where']['statement'][] = $statement;
        $this->_query['where']['value']       = array_merge($this->_query['where']['value'], $value);
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

                if (count($this->_query['where']['statement']) > 0) {
                    $sql .= ' WHERE (' . implode(') AND (', $this->_query['where']['statement']) . ')';
                }

            break;

            case self::TYPE_INSERT:
                $sql .= 'INSERT INTO ' . $this->_from;

                if (count($this->_data) === 0) {
                    return false;
                }

                $columns = array();
                $bind    = array();

                $sql .= ' (' . implode(', ', array_keys($this->_data)) . ') VALUES (:' . implode(', :', array_keys($this->_data)) . ')';

            break;

            case self::TYPE_UPDATE:
                $sql .= 'UPDATE ' . $this->_from;

                if (count($this->_data) === 0) {
                    return false;
                }

                $columns = array();
                $bind    = array();

                foreach($this->_data as $key => $value) {
                    if (is_object($value) === true && get_class($value) === "simpleframework\Raw") {
                        $columns[] = $key . ' = ' . $value;
                        unset($this->_data[$key]);
                    } else {
                        $columns[]        = $key . ' = :' . $key;
                        $bind[':' . $key] = $value;
                    }
                }

                $sql .= ' SET ' . implode(', ', $columns);

                if (count($this->_where) > 0) {
                    $sql .= ' WHERE (' . implode(') AND (', $this->_where) . ')';
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

                if (count($this->_query['where']['statement']) > 0) {
                    $sql .= ' WHERE (' . implode(') AND (', $this->_query['where']['statement']) . ')';
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


                /*
                $sqlColumn = array();

                foreach($this->_targets as $target) {
                    list($table, $alias) = self::parseTableName($target);
                    $columns = $this->_meta->getColumns($table);

                    foreach(array_keys($columns) as $column) {
                        $sqlColumn[] = $alias . '.' . $column . ' as ' . $alias . '_' . $column;
                    }
                }

                foreach($this->_query['select'] as &$select) {
                    if (strpos($select, '*') !== false && count($sqlColumn) > 0) {
                        $select = str_replace('*', implode(', ', $sqlColumn), $select);
                    }
                }
                */

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

        return $this->_query['where']['value'];

    }


    public function first()
    {

        $result = $this->execute();
        $data = $this->_processRow($result);
        $this->_stmtData->close();

        $object = $this->_metadata->mapToObjects($data, $this->_targets);

        return $object;

    }


    protected function _processRow(\mysqli_result $result)
    {

        $data   = $result->fetch_array(MYSQLI_NUM);
        $fields = $result->fetch_fields();

        foreach($fields as $i => &$field) {
            $field->value = $data[$i];
        }

        return $fields;

    }


    public function execute()
    {

        $sql = $this->getSql();

        preg_match_all('/:([a-zA-Z_-]+)/', $sql, $names);
        $sql = preg_replace('/:([a-zA-Z_-]+)/', '?', $sql);
        $names = $names[1];

        $types  = "";
        $values = array();
        $data   = $this->getValues();

        foreach($names as $name) {
            $values[] = &$data[$name];

            switch(gettype($data[$name])) {
                case "integer":
                    $types .= "i";
                break;

                case "double":
                    $types .= "f";
                break;

                default:
                    $types .= "s";
            }
        }


        if ($types !== "") {
            array_unshift($values, $types);
        }

        $connection = self::$_connections[$this->_connection];
        $this->_stmtData = $connection->prepare($sql);

        if ($types !== "") {
            call_user_func_array(array($this->_stmtData, 'bind_param'), $values);
        }

        $start = microtime(true);

        if ($this->_stmtData !== false) {
            $this->_stmtData->execute();
        }

        $this->notify(array(
            'duration' => microtime(true) - $start,
            'sql'      => $sql,
            'params'   => $this->getValues(),
            'error'    => array(
                'state'   => $connection->sqlstate,
                'code'    => $connection->errno,
                'message' => $connection->error
            )
        ));

        if ($this->_stmtData == false) {
            throw new \Exception($connection->error, $connection->errno);
        }

        switch ($this->_query['type']) {
            case self::TYPE_UPDATE:
            case self::TYPE_DELETE:
                $result = $this->_stmtData->affected_rows;
            case self::TYPE_INSERT:
                $result = self::$_connections[$this->_connection]->insert_id;
            case self::TYPE_SELECT;
                $result = $this->_stmtData->get_result();
        }

        $this->_numberRows = $this->count();

        return $result;

    }


    public static function parseTableName($table)
    {

        $targetExploded = explode(' ', $table, 2);
        $table = $targetExploded[0];

        if (isset($targetExploded[1]) === true) {
            $alias = $targetExploded[1];
        } else {
            $alias = $table;
        }

        $alias = preg_replace("/^AS /i", "", $alias);

        return array($table, $alias);

    }


    /** iterator **/
/*
    public function rewind()
    {

        $this->_stmtData = // force rewind cursor to 0
        $this->_itPosition = 0;
     //
        $this->_itStmt     = $this->_execute();
    //    $this->next();

    }


    public function valid()
    {

        if ($this->_itCurrent[1] === false) {
            return false;
        } else {
            return true;
        }

    }


    public function current()
    {

        $data = $this->_stmtData->fetch(\PDO::FETCH_ASSOC);
        $this->_itCurrent = $this->_meta->mapToObjects($data, $this->_targets);

        return $this->_itCurrent;

    }


    public function key()
    {

        return $this->_itCurrent->getId();

    }

    public function next()
    {

        if ($this->_itCurrent[0] === $this->_itPosition) {
            return $this->_itCurrent[1];
        }

        $this->_itCurrent[0] = $this->_itPosition;

        $data = $this->_itStmt->fetch(\PDO::FETCH_ASSOC);
        $this->_itCurrent[1] = $this->_meta->mapToObjects($data, $this->_targets);

        } else {
            $this->_itCurrent[1] = false;
        }

        ++$this->_itPosition;

    }



*/




    /** countable **/

    public function count()
    {

        if ($this->_numberRows !== null) {
            return $this->_numberRows;
        }

        $sql = 'SELECT FOUND_ROWS()';
        $stmt = self::$_connections[$this->_connection]->prepare($sql);

        $start = microtime(true);
        $stmt->execute();

        $this->notify(array(
            'duration' => microtime(true) - $start,
            'sql'      => $sql,
            'params'   => $this->getValues(),
            'error'    => array(
                'state'   => $this->_stmtData->sqlstate,
                'code'    => $this->_stmtData->errno,
                'message' => $this->_stmtData->error
            )
        ));

        $data = $this->_processRow($stmt->get_result());
        $stmt->close();

        return (int) $data[0]->value;

    }


}