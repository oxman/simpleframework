<?php

namespace simpleframework\Norm;


class ModelDependencyInjection
{

    protected static $_metadata = null;
    protected static $_query = null;


    public static function setQuery(Query $query)
    {

        self::$_query = $query;

    }


    public static function getQuery()
    {

        if (self::$_query === null) {
            self::$_query = new Query();
        }

        return self::$_query;

    }


    public static function setMetadata(Metadata $metadata)
    {

        self::$_metadata = $metadata;

    }


    public static function getMetadata()
    {

        if (self::$_metadata === null) {
            self::$_metadata = Metadata::getInstance();
        }

        return self::$_metadata;

    }

}


class Model
{


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

        $metadata = ModelDependencyInjection::getMetadata();
        $table = $metadata->getTable(get_called_class());
        $columnInfo = $metadata->getColumnByName($table, $name);

        $q = ModelDependencyInjection::getQuery();
        $q->from($table);
        $q->where($columnInfo['key'] . ' = :value', array(':value' => $value[0]), false);

        if ($action === 'get') {
            return $q->first();
        } else {
            return $q;
        }

    }


    public function __call($name, $value)
    {

        $action = substr($name, 0, 3);
        $name   = lcfirst(substr($name, 3));

        // if the method doesn't start with set or get, we remap the call in get
        // feature for Twig
        if ($action !== 'set' && $action !== 'get') {
            return $this->{'get' . ucfirst($name)}();
        }

        $name = $this->_findExistingProperty($name);
        $metadata = ModelDependencyInjection::getMetadata();
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
        $metadata = ModelDependencyInjection::getMetadata();
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

        $q = ModelDependencyInjection::getQuery();

        if ($mode === 'insert') {
            $id = $q->insert($table)
                    ->set($columns)
                    ->execute();
        } else {
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
        $metadata = ModelDependencyInjection::getMetadata();
        $table    = $metadata->getTable($class);
        $column   = $metadata->getPrimary($table);

        if ($column === null) {
            return $class;
        }

        return $class . '#' . $this->$column['params']['name'];

    }


}