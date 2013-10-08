<?php

namespace simpleframework;

/**
 * Example to find user by primary key from controller :
 *  User::find(217);
 *
 * Example basic modification of an user :
 *  $user = User::find(217);
 *  $user->setEmail('Abi@bi.com');
 *  $user->save();
 */

class Model
{

    protected $_models = array();


    public function init($data)
    {

        if (get_called_class() !== 'Object') {
            foreach($data as $name => $value) {
                $set = 0;

                list($subMeta, $subModel, $subModelName) = $this->_findSubModel($name);

                if ($subMeta !== false && $subMeta !== '(None)'
                    && array_key_exists('name', $subMeta) === true) {

                    if (property_exists($this, $subModelName) === false ||
                        strtolower(get_class($this->{$subModelName})) !== strtolower($subModel)) {
                        $this->{$subModelName} = new $subModel;
                        unset($this->{$subModelName}->_models);
                    }

                    $this->{$subModelName}->{'set' . ucfirst($subMeta['name'])}($value);
                    $set = 1;

                }

                $meta = $this->getMeta(get_class($this), $name);

                if (is_array($meta) === true) {
                    // its a property of object
                    if (array_key_exists('name', $meta) === true) {
                        $this->{$meta['name']} = $value;
                        $set = 1;
                    }
                }

                if ($set === 0) {
                    if (property_exists($this, 'extraQuery') === false) {
                        $this->extraQuery = array();
                    }
                    $this->extraQuery[$name] = $value;
                }
            }
        } else {
            foreach($data as $name => $value) {
                $this->$name = $value;
            }
        }

        unset($this->_models);

    }


    protected function _findSubModel($name)
    {

        foreach($this->_models as $model) {
            list($subModel, $subModelName) = $model;

            $subMeta = $this->getMeta($subModel, $name);

            if (is_array($subMeta) === true) {
                break;
            }

            $name = preg_replace('/^(' . preg_quote($subModelName) .')/i', '', $name);
            $subMeta = $this->getMeta($subModel, $name);

            if (is_array($subMeta) === true) {
                break;
            }
        }

        if (isset($subMeta) === true) {
            return array($subMeta, $subModel, $subModelName);
        } else {
            return array(false, false, false);
        }

    }


    public function __call($name, $value)
    {

        // if the method doesn't start with set or get, we remap the call in get
        // feature for Twig
        if (substr($name, 3) !== 'set' && substr($name, 3) !== 'get') {
            return $this->{'get' . ucfirst($name)}();
        }

        $propertyName           = lcfirst(substr($name, 3));
        $propertyNameUnderscore = strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst(substr($name, 3))));

        if (strpos($name, 'set') === 0) {
            if (property_exists($this, '_' . $propertyName) === true) {
                $this->{'_' . $propertyName} = $value[0];
            } else if (property_exists($this, $propertyName) === true) {
                $this->{$propertyName} = $value[0];
            } else if (property_exists($this, '_' . $propertyNameUnderscore) === true) {
                $this->{'_' . $propertyNameUnderscore} = $value[0];
            } else {
                $this->{$propertyNameUnderscore} = $value[0];
            }
        } else {
            if (property_exists($this, '_' . $propertyName) === true) {
                $property = '_' . $propertyName;
            } else if (property_exists($this, $propertyName) === true) {
                $property = $propertyName;
            } else if (property_exists($this, '_' . $propertyNameUnderscore) === true) {
                $property = '_' . $propertyNameUnderscore;
            } else {
                $property = $propertyNameUnderscore;
            }

            $allowNull = $this->getMeta(get_called_class(), $propertyName, 'allowNull');

            if ($allowNull === true && $this->{$property} === null) {
                return null;
            }

            // checks type in order to cast when required (Judas powered)
            $type = $this->getMeta(get_called_class(), $propertyName, 'type');

            switch ($type)
            {
                case 'int' :
                    return (int) $this->{$property};
                    break;

                case 'float' :
                    return (float) $this->{$property};
                    break;

                default :
                    return $this->{$property};
                    break;
            }
        }

    }


    public function delete()
    {

        $table   = get_called_class();
        $primary = ModelMeta::getPrimary($table);

        $columns = ModelMeta::getColumns($table);

        if (isset($columns[$primary]['name']) === true) {
            $primaryName = $columns[$primary]['name'];
        } else {
            $primaryName = $primary;
        }

        $q = new Query();
        return $q->delete($table)->where($primary . ' = :' . $primary, array(':' . $primary => $this->{$primaryName}))->execute();

    }


    public function update()
    {

        return $this->_save('update');

    }


    public function insert()
    {

        return $this->_save('insert');

    }


    public function save()
    {

        return $this->_save();

    }


    public function _save($mode = 'auto')
    {

        $table   = get_called_class();
        $primary = ModelMeta::getPrimary($table);
        $columns = ModelMeta::getColumns($table);

        $set = array();

        if (isset($columns[$primary]['name']) === true) {
            $primaryName = $columns[$primary]['name'];
        } else {
            $primaryName = $primary;
        }

        if ($primaryName === false || $this->{$primaryName} === null) {
            $insertMode = true;
        } else {
            $insertMode = false;
        }

        if ($mode === 'insert') {
            $insertMode = true;
        } elseif ($mode === 'update') {
            $insertMode = false;
        }

        $definedProperties = get_object_vars($this);

        foreach($columns as $column => $params) {
            if ($column !== $primary && ((isset($params['onSave']) === false || $params['onSave'] === true)
                && (((isset($params['onCreate']) === false || $params['onCreate'] === true) && $insertMode === true)
                    || ((isset($params['onUpdate']) === false || $params['onUpdate'] === true) && $insertMode === false)))) {

                if (isset($params['name']) === true) {
                    $name = $params['name'];
                } else {
                    $name = $column;
                }

                if ($this->{$name} !== null) {
                    $set[$column] = $this->{$name};
                } else if (isset($params['allowNull']) === true && $params['allowNull'] === true) {
                    $set[$column] = null;
                }
            }
        }

        $q = new Query();
        $q->set($set);

        if ($insertMode === true) {
            $q->insert($table);
        } else {
            $q->update($table);
            $q->where($primary . ' = :' . $primary, array(':' . $primary => $this->{$primaryName}));
        }

        $id = $q->execute();

        if ($primaryName !== false && $this->{$primaryName} === null) {
            $this->{$primaryName} = $id;
        }

        return $id;

    }


    public static function find($id)
    {

        $primary = ModelMeta::getPrimary(get_called_class());
        $q = new Query();
        $q->from(get_called_class())->where($primary . ' = :id', array(':id' => $id));

        return $q->first();

    }


    public function getMeta($table, $column=false, $property=false)
    {

        $columns = ModelMeta::getColumns($table);

        if ($column !== false) {
            $column  = strtolower($column);
        }

        if ($columns === false) {
            return '(None)';
        }

        if ($column === false) {
            return $columns;
        }

        // maybe its a property name and not a column name ?
        $find = array_filter($columns, function ($elt) use ($column) {
            return $elt['internal_name'] === $column || $elt['internal_name'] === '_' . $column;
        });

        if (count($find) > 0) {
            $column = key($find);
        }

        if (isset($columns[$column]) === true && $property === false) {
            return $columns[$column];
        }

        if (isset($columns[$column]) === true && $property === '_key') {
            return $column;
        }

        if (isset($columns[$column][$property]) === true) {
            return $columns[$column][$property];
        }

        // we can't return false because it can be a real value
        return '(None)';

    }


    public function addModel($table)
    {

        if (strpos($table, ' ') !== false) {
            list($cleanTable, $name) = explode(' ', $table, 2);
            $name = preg_replace('/^AS /i', '', $name);
        } else {
            $cleanTable = $table;
        }

        $class = ModelMeta::getObjectName($cleanTable);

        if ($class === false) {
            $model = "Object";
        } else {
            $model = $class;
        }

        // no alias ? the object name should be the class name
        if (isset($name) === false) {
            $name = $model;
        }

        $this->_models[] = array($model, $name);

    }


    public function __toString()
    {

        $string = get_called_class();

        if (property_exists($this, '_id') === true) {
            $string .= '#' . $this->_id;
        }

        return $string;

    }


}