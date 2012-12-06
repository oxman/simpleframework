<?php

namespace simpleframework\Norm;

class Metadata
{


    protected $_metadata;
    protected static $_instance;


    public static function getInstance($directory = '/app/model/*.php')
    {

        if (!isset(self::$_instance)) {
            self::$_instance = new self($directory);
        }

        return self::$_instance;

    }


    protected function __construct($directory)
    {

        $classes  = array();
        $metadata = array();

        foreach(glob(ROOT . $directory) as $file) {
                preg_match_all('/\s+class (.+?)\s/i', file_get_contents($file), $matches);
                $classes = array_merge($classes, $matches[1]);
        }

        foreach($classes as $class) {
            $model = strtolower($class);

            $params = array();


            $reflectionClass = new \ReflectionClass($class);

            $classCommentInfos = $this->_parseComment($reflectionClass->getDocComment());

            if (isset($classCommentInfos['name']) === false) {
                continue;
            }

            $metadata[$classCommentInfos['name']] = array();
            $metadata[$classCommentInfos['name']]['columns'] = array();
            $metadata[$classCommentInfos['name']]['class'] = $class;

            foreach($reflectionClass->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {

                $reflectionProperty = new \ReflectionProperty($model, $property->name);
                $commentInfos = $this->_parseComment($reflectionProperty->getDocComment());

                if ($commentInfos === null) {
                    continue;
                }

                if (isset($commentInfos['type']) === false) {
                    $commentInfos['type'] = "auto";
                }

                $param   = array();
                $sqlName = $commentInfos['name'];
                $commentInfos['name'] = $property->name;
                $params[$sqlName]     = $commentInfos;


            }
            $metadata[$classCommentInfos['name']]['columns'] = $params;

        }

        $this->_metadata = $metadata;

    }


    protected function _parseComment($comment)
    {

        if (preg_match_all('/\* orm:(.+)\((.+)\)/', $comment, $matches) > 0) {
            $param  = array_combine($matches[1], $matches[2]);

            foreach($param as $key => $value) {
                if ($value === 'true') {
                    $param[$key] = true;
                } else if ($value === 'false') {
                    $param[$key] = false;
                } else if (is_numeric($value) === true) {
                    $param[$key] = (int) $value;
                } else if (strpos($value, ',') !== false) {
                    $array = explode(',', $value);
                    $param[$key] = array_map('trim', $array);
                }
            }

            return $param;

        }

        return null;

    }


    public function getTable($class)
    {

        foreach($this->_metadata as $table => $columns) {
            if ($columns['class'] === $class) {
                return $table;
            }
        }

        return null;

    }


    public function getPrimary($table)
    {

        list($table) = explode(' ', $table);

        if (isset($this->_metadata[$table]) === false) {
            return null;
        }

        foreach($this->_metadata[$table]['columns'] as $sqlName => $column) {
            if (isset($column['primary']) === true && $column['primary'] === true) {
                return array(
                    'key'    => $sqlName,
                    'params' => $column);
            }
        }

        return null;

    }


    public function getColumns($table)
    {

        list($table) = explode(' ', $table);

        if (isset($this->_metadata[$table]) === false) {
            return array();
        }

        if (count($this->_metadata[$table]['columns']) === 0) {
            return array();
        }

        return $this->_metadata[$table]['columns'];

    }


    public function getColumnByName($table, $propertyName)
    {

        list($table) = explode(' ', $table);

        if (isset($this->_metadata[$table]) === false) {
            return null;
        }

        if (count($this->_metadata[$table]['columns']) === 0) {
            return null;
        }

        foreach($this->_metadata[$table]['columns'] as $column) {
            if ($column['name'] === $propertyName) {
                return $column;
            }
        }

        return null;

    }


    public function getColumnByKey($table, $sqlName)
    {

        list($table) = explode(' ', $table);

        if (isset($this->_metadata[$table]['columns'][$sqlName]) === false) {
            return null;
        }

        return $this->_metadata[$table]['columns'][$sqlName];

    }


    public function getClass($table)
    {

        list($table) = explode(' ', $table);

        if (isset($this->_metadata[$table]['class']) === false) {
            return null;
        }

        return $this->_metadata[$table]['class'];

    }


    public function mapToObjects($columns, $targets)
    {
        $mainTarget = array_shift($targets);

        list($table, $alias) = Query::parseTableName($mainTarget);
        $object = $this->mapToObject($columns, $table, $alias);

        if ($object === null) {
            return null;
        }

        foreach($targets as $target) {
            list($table, $alias) = Query::parseTableName($target);
            $subObject = $this->mapToObject($columns, $table, $alias);
            $object->{'set' . ucfirst($alias)}($subObject);

        }

        return $object;

    }


    public function mapToObject($columns, $table, $alias)
    {

        $class = self::getClass($table);

        if ($class === false) {
            return null;
        }

        if ($columns === false) {
            return null;
        }

        $object = new $class;

        foreach($columns as $column) {
            if (is_object($column) === false) { var_dump($column); }
            if ($alias !== $column->table) {
                continue;
            }

            $columnInfo = $this->getColumnByKey($table, $column->orgname);

            // not a column of the model
            if ($columnInfo === false) {
                $method = 'set' . ucfirst($column->orgname);
            } else {
                $method = 'set' . ucfirst(substr($columnInfo['name'], 1));
            }

            $object->$method($column->value);

        }

        return $object;

    }


}