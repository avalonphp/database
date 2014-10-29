<?php
/*
 * Avalon
 * Copyright 2011-2014 Jack Polgar
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Avalon\Database;

use ReflectionClass;
use Avalon\Database\QueryBuilder;

/**
 * Database Model.
 *
 * @author Jack Polgar <jack@polgar.id.au>
 */
abstract class Model
{
    /**
     * Connection name.
     *
     * @var string
     */
    protected static $_connectionName = 'default';

    /**
     * Table name.
     *
     * @var string
     */
    protected static $_tableName;

    /**
     * Table schema.
     *
     * @var array
     */
    protected static $_schema = [];

    /**
     * Column data types to convert to/from.
     *
     * @var array
     */
    protected static $_dataTypes = [];

    /**
     * Whether or not the model already exists in the database.
     *
     * @var bool
     */
    protected $_isNew;

    /**
     * @param array $data  Model data.
     * @param bool  $isNew Whether or not it exists in the database.
     */
    public function __construct(array $data = [], $isNew = true)
    {
        $this->_isNew = $isNew;

        foreach (static::schema() as $field => $properties) {
            $this->{$field} = $properties->getdefault();
        }

        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }

        if (!$isNew) {
            // Convert data from a safely storable format
            foreach (static::$_dataTypes as $column => $type) {
                if (isset($this->{$column})) {
                    if ($type == 'json_array') {
                        $this->{$column} = json_decode($this->{$column}, true);
                    }
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // Class Methods

    /**
     * @param integer $id
     *
     * @return Model
     */
    public static function find($id)
    {
        return static::where('id = ?', $id)->fetch();
    }

    /**
     * Creates a row in the database and returns a new model object.
     *
     * @param array $data Model data.
     *
     * @return Model|integer
     */
    public static function create($data)
    {
        $result = static::insert($data);

        if ($result) {
            return static::find(static::connection()->lastInsertId());
        } else {
            return $result;
        }
    }

    /**
     * Creates a row in the database.
     *
     * @param array $data Model data.
     *
     * @return integer
     */
    public static function insert($data)
    {
        $data = static::convertDataTypes($data);
        return static::connection()->insert(static::tableName(), $data);
    }

    /**
     * Returns an array of all rows as models.
     *
     * @return Model[]
     */
    public static function all()
    {
        return static::select()->fetchAll();
    }

    /**
     * @param string  $predicates The restriction predicates.
     * @param mixed   $value      Value of the restriction.
     * @param integer $type       One of the PDO::PARAM_* constants.
     *
     * @return QueryBuilder
     */
    public static function where($predicates, $value = null, $type = \PDO::PARAM_STR)
    {
        return static::select()->where($predicates, $value, $type);
    }

    /**
     * @param mixed $select Columns to select.
     *
     * @return QueryBuilder
     */
    public static function select($select = '*')
    {
        $builder = new QueryBuilder(static::connection());
        $builder->setModel(get_called_class());

        return $builder->select($select)->from(static::tableName(), static::tableName());
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public static function connection()
    {
        return ConnectionManager::getConnection(static::$_connectionName);
    }

    /**
     * @return string
     *
     * @todo Proper class to table name conversion.
     */
    public static function tableName()
    {
        if (static::$_tableName) {
            return static::$_tableName;
        }

        $classInfo = new ReflectionClass(get_called_class());

        // This is a hack for now, totally temporary
        return strtolower($classInfo->getShortName() . 's');
    }

    /**
     * Returns the table schema.
     *
     * @return \Doctrine\DBAL\Schema\Column[]
     */
    public static function schema()
    {
        if (isset(static::$_schema[static::tableName()])) {
            return static::$_schema[static::tableName()];
        }

        return static::loadSchema();
    }

    /**
     * Loads the table schema.
     *
     * @return \Doctrine\DBAL\Schema\Column
     */
    public static function loadSchema()
    {
        $schemaManager = static::connection()->getSchemaManager();
        static::$_schema[static::tableName()] = $schemaManager->listTableColumns(static::tableName());

        unset($schemaManager);

        return static::$_schema[static::tableName()];
    }

    /**
     * Convert special data to a safely storable format.
     *
     * @param array $data
     *
     * @return array
     */
    public static function convertDataTypes(array $data)
    {
        foreach (static::$_dataTypes as $column => $type) {
            if ($type == 'json_array') {
                $data[$column] = json_encode($data[$column]);
            }
        }

        return $data;
    }

    // -------------------------------------------------------------------------
    // Instance Methods

    /**
     * Returns model data.
     *
     * @return array
     */
    public function getData()
    {
        $data = [];
        foreach (static::schema() as $field => $property) {
            $data[$field] = $this->{$field};
        }

        return $data;
    }

    /**
     * Saves model data to the database.
     *
     * @return bool
     */
    public function save()
    {
        // Create row if this is a new model
        if ($this->_isNew) {
            $result = static::insert($this->getData());

            if ($result) {
                $this->_isNew = false;
                $this->id     = static::connection()->lastInsertId();
            }
        } else {
            $data = static::convertDataTypes($data);
            $result = static::connection()->update(static::tableName(), $data, [
                'id' => $this->id
            ]);
        }

        return $result > 0 ? true : false;
    }
}