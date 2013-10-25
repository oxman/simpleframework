<?php

namespace simpleframework\Norm;


class Model
{


    private static $_metadata;
    private static $_staticQuery;


    protected static function _getQuery()
    {

        return new Query();

    }


    public static function setMetadata(Metadata $metadata)
    {

        self::$_metadata = $metadata;

    }


    public static function getMetadata()
    {

        if (isset(self::$_metadata) === false) {
            return Metadata::getInstance();
        } else {
            return self::$_metadata;
        }

    }


    public static function staticSetQuery(Query $query)
    {

        self::$_staticQuery = $query;

    }


    public static function staticGetQuery()
    {

        if (isset(self::$_staticQuery) === false) {
            return new Query();
        } else {
            return self::$_staticQuery;
        }

    }


    public function setQuery(Query $query)
    {

        $this->query = $query;

    }


    public function getQuery()
    {

        if (isset($this->query) === false) {
            return new Query();
        } else {
            return $this->query;
        }

    }


    public static function __callStatic($name, $value)
    {

        $action = substr($name, 0, 5);

        if ($action === 'getBy') {
            $action = 'get';
            $name = lcfirst(substr($name, 5));
        } else {
            $action = 'find';
            $name = lcfirst(substr($name, 6));
        }

        $names = preg_split('/And(?<![A-Z])/', $name);

        $metadata = self::getMetadata();
        $table = $metadata->getTable(get_called_class());

        $q = self::staticGetQuery();
        $q->from($table);

        $conditions = array();
        $values = array();

        foreach($names as $i => $name) {
            $columnInfo = $metadata->getColumnByName($table, lcfirst($name));
            $conditions[] = $columnInfo['key'] . ' = :' . $columnInfo['key'];
            $values = array_merge($values, array(':' . $columnInfo['key'] => $value[$i]));
        }

        $q->where(implode(' AND ', $conditions), $values, false);

        if ($action === 'get') {
            return $q->first();
        } else {
            return $q;
        }

    }


    public function __call($name, $value)
    {

        $action = substr($name, 0, 3);

        // if the method doesn't start with set or get, we remap the call in get
        // feature for Twig
        if ($action !== 'set' && $action !== 'get') {
            return $this->{'get' . ucfirst($name)}();
        }

        $name   = lcfirst(substr($name, 3));

        $name = $this->_findExistingProperty($name);
        $metadata = self::getMetadata();
        $table  = $metadata->getTable(get_called_class());
        $columnInfo = $metadata->getColumnByName($table, $name);

        if ($columnInfo === null) {
            $type = 'auto';
        } else {
            $type = $columnInfo['type'];
        }

        if ($action === 'set') {
            $this->$name = $this->_cast($value[0], $type);
            return $this;
        } else {
            return $this->_cast($this->$name, $type);
        }

    }


    // Find property in the following order : _myProperty, myProperty, _my_property, my_property, $name
    protected function _findExistingProperty($name)
    {

        $nameUnderscore = strtolower(preg_replace('/([A-Z])/', '_$1', $name));

        if (property_exists($this, '_' . $name) === true) {
            return '_' . $name;
        } else if (property_exists($this, $name) === true) {
            return $name;
        } else if (property_exists($this, '_' . $nameUnderscore) === true) {
            return '_' . $nameUnderscore;
        } else if (property_exists($this, $nameUnderscore) === true) {
            return $nameUnderscore;
        }

        return $name;

    }


    protected function _cast($value, $type)
    {

        if (is_object($value) === true) {
            return $value;
        }

        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'int':
                return intval($value);
                break;

            case 'double':
                return floatval($value);
                break;

            case 'datetime':
                return new \Datetime($value);
                break;

            default:
                return $value;
                break;
        }

    }


    public function update()
    {

        return $this->_save('update');

    }


    public function save()
    {

        return $this->_save('insert');

    }


    protected function _save($mode)
    {

        $class    = get_called_class();
        $metadata = self::getMetadata();
        $table    = $metadata->getTable($class);
        $column   = $metadata->getPrimary($table);

        $properties = get_object_vars($this);
        $columns = array();

        foreach($properties as $name => $value) {
            if ($value != null) {
                $columnInfo = $metadata->getColumnByName($table, $name);

                if (isset($columnInfo['key']) === true) {
                    $columns[$columnInfo['key']] = $value;
                }
            }
        }

        $q = $this->getQuery();

        if ($mode === 'insert') {
            $id = $q->insert($table)
                    ->set($columns)
                    ->execute();
        } else {
            unset($columns[$column['key']]);

            $id = $q->update($table)
                    ->set($columns)
                    ->where($column['key'] . ' = :' . $column['key'], array(':' . $column['key'] => $this->$column['params']['name']))
                    ->execute();
        }

        if (is_numeric($id) === true) {
            if ($mode === 'insert') {
                $this->$column['params']['name'] = $id;
            }
            return true;
        } else {
            return false;
        }

    }


    public function __toString()
    {

        $class    = get_called_class();
        $metadata = self::getMetadata();
        $table    = $metadata->getTable($class);
        $column   = $metadata->getPrimary($table);

        if ($column === null) {
            return $class;
        }

        return $class . '#' . $this->$column['params']['name'];

    }


}