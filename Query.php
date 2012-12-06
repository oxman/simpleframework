<?php

namespace simpleframework {

/**
 * Example select :
 *  $q = new simpleframework\Query;
 *  $q->select('email')->from('user')->where('active = :active', array(':active' => $active));
 *  foreach($q as $row) { ... }
 *
 * Example insert :
 *  $q = new simpleframework\Query;
 *  $id = $q->insert('user')->set(array('email' => $email, 'password' => $password, 'active' => 1))->execute();
 *
 * Example update :
 *  $q = new simpleframework\Query;
 *  $affected = $q->update('user')->set(array('active' => 0))->where('email = :email', array(':email' => $email))->execute();
 *
 * Example delete :
 *  $q = new simpleframework\Query;
 *  $affected = $q->delete('user')->where('active = :active', array(':active' => 0))->execute();
 */

class Query implements \Iterator, \Countable
{

    protected static $_connections = array();
    protected static $_currentConnection;
    protected $_driver;
    protected $_type       = "select";
    protected $_select     = array();
    protected $_from;
    protected $_fromObject = "Object";
    protected $_data       = array();
    protected $_where      = array();
    protected $_whereValue = array();
    protected $_order;
    protected $_group;
    protected $_having    = array();
    protected $_limit;
    protected $_union     = array();
    protected $_innerJoin = array();
    protected $_leftJoin  = array();
    protected $_rightJoin = array();
    protected $_returning = array();
    protected $_forceAnonymous = false;

    /** iterator values **/
    protected $_itPosition = 0;
    protected $_itCurrent  = array(-1, false);
    protected $_itStmt;


    public function __construct($options = array())
    {

        if (isset($options['connection']) === true) {
            $connection = $options['connection'];
        } else {
            $connection = 'default';
        }

        if (isset($options['anonymous']) === true && $options['anonymous'] === true) {
            $this->_forceAnonymous = true;
        }

        $db = Kernel::getConfig('db');

        self::$_currentConnection = $connection;
        $this->_driver = $db[$connection]['driver'];

        if (isset(self::$_connections[$connection]) === false) {
            self::$_connections[$connection] = new \PDO($db[$connection]['driver']
                     . ':dbname=' . $db[$connection]['database']
                     . ';host=' . $db[$connection]['hostname'],
                     $db[$connection]['username'], $db[$connection]['password']);

            if (simpleframework_ENV === "prod") {
                self::$_connections[$connection]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
            } else {
                self::$_connections[$connection]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            }

            self::$_connections[$connection]->query("SET NAMES 'utf8'");

        }

        return $this;

    }


    protected function _setFrom($table)
    {

        /* on prend que le nom de la table si c'est un alias */
        if (strpos($table, ' ') !== false) {
            list($cleanTable, $rest) = explode(' ', $table, 2);
        } else {
            $cleanTable = $table;
            $rest = '';
        }

        $rest = ' ' . $rest;

        $meta = ModelMeta::getTable($cleanTable);
        if ($meta === false) {
            $class = ModelMeta::getObjectName($cleanTable);
            if ($class === false) {
                $class = "Object";
            }
            $this->_fromObject = $class;
            $this->_from       = $table;
        } else {
            $this->_fromObject = $cleanTable;
            $this->_from       = $meta['name'] . $rest;
        }

    }


    public function from($table)
    {

        $this->_type = 'select';
        $this->_setFrom($table);
        return $this;

    }


    public function insert($table)
    {

        $this->_type = 'insert';
        $this->_setFrom($table);
        return $this;

    }


    public function update($table)
    {

        $this->_type = 'update';
        $this->_setFrom($table);
        return $this;

    }


    public function delete($table)
    {

        $this->_type = 'delete';
        $this->_setFrom($table);
        return $this;

    }


    public function select($select)
    {

        $this->_select[] = $select;
        return $this;

    }


    public function set($data)
    {

        $this->_data = $data;
        return $this;

    }


    public function union(Query $q)
    {

        $this->_union[] = $q;
        return $this;

    }


    public function where($where, $value=array())
    {

        $this->_where[]    = $where;
        $this->_whereValue = array_merge($this->_whereValue, $value);
        return $this;

    }


    public function order($order)
    {

        $this->_order = $order;
        return $this;

    }


    public function group($group)
    {

        $this->_group = $group;
        return $this;

    }


    public function having($having)
    {

        $this->_having[] = $having;
        return $this;

    }


    public function limit($from, $to)
    {

        $this->_limit = array($from, $to);
        return $this;

    }


    public function innerJoin($table, $condition)
    {

        $this->_innerJoin[$table] = $condition;
        return $this;

    }


    public function leftJoin($table, $condition)
    {

        $this->_leftJoin[$table] = $condition;
        return $this;

    }


    public function rightJoin($table, $condition)
    {

        $this->_rightJoin[$table] = $condition;
        return $this;

    }


    public function returning($columns=array())
    {

        $this->_returning = $columns;
        return $this;

    }


    protected function _makeQuery($skipLimit=false, $skipFoundRows=false)
    {

        $sql = "";

        switch ($this->_type) {
            case 'delete':
                $sql .= 'DELETE FROM ' . $this->_from;

                if (count($this->_where) === 0) {
                    return false;
                }

                $sql .= ' WHERE (' . implode(') AND (', $this->_where) . ')';

                break;

            case 'insert':
                $sql .= 'INSERT INTO ' . $this->_from;

                if (count($this->_data) === 0) {
                    return false;
                }

                $columns = array();
                $bind    = array();

                $sql .= ' (' . implode(', ', array_keys($this->_data)) . ') VALUES (:' . implode(', :', array_keys($this->_data)) . ')';

                break;

            case 'update':
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

            case 'select':

                if ($skipFoundRows === false) {
                    $sql .= 'SELECT SQL_CALC_FOUND_ROWS';
                } else {
                    $sql .= 'SELECT';
                }

                if (count($this->_select) === 0) {
                    $sql  .= ' *';
                } else {
                    $sql  .= ' ' . implode(', ', $this->_select);
                }

                $sql .= ' FROM ';
                if (count($this->_union) > 0) {
                    $sqlQuery = array();
                    foreach($this->_union as $subQ) {
                        $sqlQuery[] = $subQ->getSql(true, false, true);
                    }

                    $sql .= '((' . implode(') UNION (' , $sqlQuery) . ')) as SQTT';
                } else {
                    $sql .= $this->_from;
                }

                if (count($this->_innerJoin) > 0) {
                    foreach ($this->_innerJoin as $table => $condition) {
                        $sql .= ' INNER JOIN ' . $table . ' ON (' . $condition . ')';
                    }
                }

                if (count($this->_leftJoin) > 0) {
                    foreach ($this->_leftJoin as $table => $condition) {
                        $sql .= ' LEFT JOIN ' . $table . ' ON (' . $condition . ')';
                    }
                }

                if (count($this->_rightJoin) > 0) {
                    foreach ($this->_rightJoin as $table => $condition) {
                        $sql .= ' RIGHT JOIN ' . $table . ' ON (' . $condition . ')';
                    }
                }

                if (count($this->_where) > 0) {
                    $sql .= ' WHERE (' . implode(') AND (', $this->_where) . ')';
                }

                if (isset($this->_group) === true) {
                    $sql .= ' GROUP BY ' . $this->_group;
                }

                if (count($this->_having) > 0) {
                    $sql .= ' HAVING (' . implode(') AND (', $this->_having) . ')';
                }

                if (isset($this->_order) === true) {
                    $sql .= ' ORDER BY ' . $this->_order;
                }

                if ($skipLimit === false && isset($this->_limit) === true) {
                    $sql .= ' LIMIT ' . $this->_limit[1] . ' OFFSET ' . $this->_limit[0];
                }

                break;

         }

        if ($this->_driver === 'pgsql' && in_array($this->_type, array('update', 'insert')) === true
            && count($this->_returning) > 0) {

            $sql .= ' RETURNING ' . implode(', ', $this->_returning);
        }

        return $sql;

    }


    protected function _getValues()
    {

        $data = array();
        if (count($this->_data) > 0) {
            foreach($this->_data as $key => $value) {
                $data[':' . $key] = $value;
            }
        }

        return array_merge($data, $this->_whereValue);

    }


    public function first()
    {

        $stmt = $this->_execute();
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($data !== false) {
            if ($this->_forceAnonymous === true) {
                $m = new \Object;
            } else {
                $m = new $this->_fromObject();
            }

            foreach($this->_innerJoin as $table => $condition) {
                $m->addModel($table);
            }
            foreach($this->_leftJoin as $table => $condition) {
                $m->addModel($table);
            }
            foreach($this->_rightJoin as $table => $condition) {
                $m->addModel($table);
            }

            $m->init($data);

            return $m;
        } else {
            return false;
        }

    }


    public function execute()
    {

        $sql = $this->_makeQuery();
        $this->_stmt = self::$_connections[self::$_currentConnection]->prepare($sql);

        Notification::call('sql');
        $this->_stmt->execute($this->_getValues());
        Notification::call('end',
            array('key' => $sql, 'params' => $this->_getValues(), 'error' => $this->_stmt->errorInfo()));

        switch ($this->_type) {
            case 'update':
            case 'delete':
                return $this->_stmt->rowCount();
            case 'insert':
                return self::$_connections[self::$_currentConnection]->lastInsertId(); /** attention pgsql **/
        }

    }


    protected function _execute()
    {

        $sql = $this->_makeQuery();

        $stmt = self::$_connections[self::$_currentConnection]->prepare($sql);

        Notification::call('sql');
        $stmt->execute($this->_getValues());
        Notification::call('end',
            array('key' => $sql, 'params' => $this->_getValues(), 'error' => $stmt->errorInfo()));

        return $stmt;

    }


    public function getSql($withValues=false, $skipLimit=false, $skipFoundRows=false)
    {

        $sql = $this->_makeQuery($skipLimit, $skipFoundRows);

        if ($withValues === true) {
            foreach($this->_getValues() as $key => $value) {
                $sql = str_replace($key, self::$_connections[self::$_currentConnection]->quote($value), $sql);
            }
        }

        return $sql;

    }


    public function __toString()
    {

        return $this->getSql(true);

    }



    public function getValues()
    {

        return $this->_getValues();

    }


    public function getReturning()
    {

        return $this->_stmt->fetch(\PDO::FETCH_ASSOC);

    }


    /** iterator **/

    public function rewind()
    {

        $this->_itCurrent  = array(-1, false);
        $this->_itPosition = 0;
        $this->_itStmt     = $this->_execute();
        $this->next();

    }


    public function next()
    {

        if ($this->_itCurrent[0] === $this->_itPosition) {
            return $this->_itCurrent[1];
        }

        $this->_itCurrent[0] = $this->_itPosition;

        $data = $this->_itStmt->fetch(\PDO::FETCH_ASSOC);

        if ($data !== false) {
            if ($this->_forceAnonymous === true) {
                $m = new \Object;
            } else {
                $m = new $this->_fromObject();
            }

            foreach($this->_innerJoin as $table => $condition) {
                $m->addModel($table);
            }
            foreach($this->_leftJoin as $table => $condition) {
                $m->addModel($table);
            }
            foreach($this->_rightJoin as $table => $condition) {
                $m->addModel($table);
            }

            $m->init($data);

            $this->_itCurrent[1] = $m;

        } else {
            $this->_itCurrent[1] = false;
        }

        ++$this->_itPosition;

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

        return $this->_itCurrent[1];

    }


    public function key()
    {

        return $this->_itPosition;

    }


    /** countable **/

    public function count()
    {

        $sql = 'SELECT FOUND_ROWS()';
        $stmt = self::$_connections[self::$_currentConnection]->prepare($sql);

        Notification::call('sql');
        $stmt->execute($this->_getValues());
        Notification::call('end',
            array('key' => $sql, 'params' => $this->_getValues(), 'error' => $stmt->errorInfo()));

        $data = $stmt->fetch();

        return $data[0];

    }


}

function Raw($string) {
    return new Raw($string);
}

class Raw {

    private $_content = '';

    public function __construct($string)
    {

        $this->_content = $string;

    }

    public function __toString()
    {

        return $this->_content;

    }

}

}

namespace {
    class Object extends simpleframework\Model {}
}