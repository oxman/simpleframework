<?php

namespace simpleframework;

class ModelMeta
{

    protected static $_metadata;


    public function __construct()
    {

        if (file_exists($file = ROOT . '/tmp/cache/model/all.serialized') === true) {
            self::$_metadata = unserialize(file_get_contents($file));
        }


        $metadata = array();
        $metadata['__reverse'] = array();
        $classes = array();

        foreach(glob(ROOT . '/app/model/*.php') as $file) {
                preg_match_all('/\s+class (.+?)\s/i', file_get_contents($file), $matches);
                $classes = array_merge($classes, $matches[1]);
        }

        foreach($classes as $class) {
            $model = strtolower($class);

            $metadata[$model] = array();
            $metadata[$model]['columns'] = array();
            $params = array();

            $r = new \ReflectionClass($class);

            $param = $this->_parseComment($r->getDocComment());

            if (isset($param['name']) === false) {
                $param['name'] = $model;
            }

            $metadata[$model]['table'] = $param;
            $metadata['__reverse'][$param['name']] = $model;

            foreach($r->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {

                $rc = new \ReflectionProperty($model, $property->name);
                $param = $this->_parseComment($rc->getDocComment());

                if (isset($param['type']) === false) {
                    continue;
                }

                if (isset($param['name']) === true) {
                    $property_name = $param['name'];
                } else {
                    $property_name = $property->name;
                }

                $param['name']          = $property->name;
                $param['internal_name'] = strtolower($property->name);
                $param['sql_name']      = $property_name;

                $params[$property_name] = $param;

                foreach($params[$property_name] as $key => $value) {
                    if ($key == 'primary') {
                        $metadata[$model]['primary'] = $property_name;
                    }
                }

            }
            $metadata[$model]['columns'] = $params;

        }

        //file_put_contents(ROOT . '/tmp/cache/model/all.serialized', serialize($metadata));
        self::$_metadata = $metadata;

    }


    public static function getPrimary($table)
    {

        $table = strtolower($table);

        if (isset(self::$_metadata[$table]) === false) {
            return false;
        }

        if (isset(self::$_metadata[$table]['primary']) === false) {
            return false;
        }

        return self::$_metadata[$table]['primary'];

    }


    public static function getColumns($table)
    {

        $table = strtolower($table);

        if (isset(self::$_metadata[$table]) === false) {
            return false;
        }

        if (count(self::$_metadata[$table]['columns']) === 0) {
            return false;
        }

        return self::$_metadata[$table]['columns'];

    }


    public static function getTable($table)
    {

        $table = strtolower($table);

        if (isset(self::$_metadata[$table]) === false) {
            return false;
        }

        if (count(self::$_metadata[$table]['table']) === 0) {
            return false;
        }

        return self::$_metadata[$table]['table'];

    }


    public static function getObjectName($table)
    {

        if (isset(self::$_metadata['__reverse'][$table]) === false) {
            return false;
        }

        return self::$_metadata['__reverse'][$table];

    }


    public function _parseComment($comment)
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
                    $array = array_map('trim', $array);
                    $param[$key] = $array;
                }
            }

            return $param;

        }

        return array();

    }


}