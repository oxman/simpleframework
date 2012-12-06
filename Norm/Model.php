<?php

namespace simpleframework\Norm;

class Model
{


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
        $metadata = Metadata::getInstance();
        $table  = $metadata->getTable(get_called_class());
        $column = $metadata->getColumnByName($table, $name);

        if ($column === null) {
            $type = 'auto';
        } else {
            $type = $column['type'];
        }

        $valueCasted = $this->_cast($value[0], $type);

        if ($action === 'set') {
            $this->$name = $valueCasted;
            return $this;
        } else {
            return $valueCasted;
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

        switch ($type) {
            case 'int':
                return (int) $value;
                break;

            case 'float':
                return (float) $value;
                break;

            case 'datetime':
                return new \Datetime($value);
                break;

            default :
                return $value;
                break;
        }

    }


    public function __toString()
    {

        $class    = get_called_class();
        $metadata = Metadata::getInstance();
        $table    = $metadata->getTable($class);
        $column   = $metadata->getPrimary($table);

        if ($column === null) {
            return $class;
        }

        return $class . '#' . $this->$column['params']['name'];

    }


}