<?php
/*
 * Avalon
 * Copyright 2011-2016 Jack P.
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

use DateTime;
use ReflectionClass;
use Avalon\Database\Inflector;
use Avalon\Database\QueryBuilder;
use Avalon\Database\Model\Base as BaseModel;
use Avalon\Database\Model\Errors;
use Avalon\Database\Model\Filterable;
use Avalon\Database\Model\Relatable;
use Avalon\Database\Model\Validatable;

/**
 * Database Model.
 *
 * This class breaks the PSR standards a little to separate model variables from
 * table columns.
 *
 * @author Jack P.
 */
abstract class Model extends BaseModel
{
    use Errors;
    use Filterable;
    use Relatable;
    use Validatable;

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
     * Table alias.
     *
     * @var string
     */
    protected static $_tableAlias;

    /**
     * Table schema.
     *
     * @var array
     */
    protected static $_schema = [];

    /**
     * Before filters.
     *
     * @var array
     */
    protected static $_before = [];

    /**
     * After filters.
     *
     * @var array
     */
    protected static $_after = [];

    /**
     * Validations to run.
     *
     * @var array
     */
    protected static $_validations = [];

    /**
     * Belongs-to relationships.
     *
     * @var array
     */
    protected static $_belongsTo = [];

    /**
     * Has-many relationships.
     *
     * @var array
     */
    protected static $_hasMany = [];

    /**
     * Fields to remove when converting to array.
     */
    protected static $_excludeFromArray = [];

    /**
     * Validation errors.
     */
    protected $_errors = [];

    /**
     * @param array $data  Model data.
     * @param bool  $isNew Whether or not it exists in the database.
     */
    public function __construct(array $data = [], $isNew = true)
    {
        if ($isNew) {
            foreach (static::schema() as $field => $properties) {
                $this->{$field} = $properties->getDefault() === 'NULL' ? null : $properties->getDefault();

                // If the field default is a string and has come out of the DB wrapped in single quotes
                // remove them from the start and end.
                if (strpos($this->{$field}, "'") === 0) {
                    $this->{$field} = substr(
                        substr($this->{$field}, 0, -1),
                        1
                    );
                }
            }
        }

        parent::__construct($data, $isNew);
    }

    // -------------------------------------------------------------------------
    // Class Methods

    /**
     * @param integer $id
     *
     * @return Model
     */
    public static function find($field, $value = null)
    {
        if ($value === null && !empty((int) $field)) {
            return static::where('id = :id')->setParameter('id', $field)->fetch();
        } else {
            return static::where("{$field} = :findval")->setParameter('findval', $value)->fetch();
        }
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
        $model = new static($data);

        if ($model->save()) {
            return $model;
        }

        return false;
    }

    /**
     * Creates a row in the database.
     *
     * @param array $data Model data.
     *
     * @return integer
     */
    public static function insert($data, array $types = [])
    {
        unset($data['id']);
        return static::connection()->insert(static::tableName(), $data, static::$_dataTypes + $types);
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

        $args = func_get_args();

        if (!count($args)) {
            $args = ['*'];
        }

        return call_user_func_array([$builder, 'select'], $args)
            ->from(static::tableName(), static::tableAlias());
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
     */
    public static function tableName($withPrefix = true)
    {
        if (static::$_tableName) {
            return static::$_tableName;
        }

        $classInfo = new ReflectionClass(get_called_class());
        return ($withPrefix ? static::connection()->prefix : '') . Inflector::pluralize(Inflector::tableize($classInfo->getShortName()));
    }

    /**
     * @return string
     */
    public static function tableAlias()
    {
        if (static::$_tableAlias) {
            return static::$_tableAlias;
        }

        $classInfo = new ReflectionClass(get_called_class());
        return Inflector::tableize($classInfo->getShortName());
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
            if ($this->{$field} === '') {
                // Sometimes when the default is NULL, the PHP value is string NULL instead of native null.
                $data[$field] = $property->getDefault() === 'NULL' ? null : $property->getDefault();
            } else {
                $data[$field] = $this->{$field};
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = $this->getData();

        foreach (static::$_excludeFromArray as $field) {
            unset($data[$field]);
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
        // Validate
        if (!$this->validate()) {
            return false;
        }

        $wasNew = $this->_isNew;
        $types = static::$_dataTypes;

        foreach ($types as $column => $type) {
            if (!isset($this->{$column})
            || $this->{$column} === null
            || ($type == 'datetime' && is_string($this->{$column}))) {
                unset($types[$column]);
            }
        }

        // Create row if this is a new model
        if ($this->_isNew) {
            $this->runFilters('before', 'create');

            $data = $this->getData();

            if (isset(static::schema()['created_at'])) {
                $data['created_at'] = new DateTime('now');
                $types['created_at'] = 'datetime';
            }

            $result = static::insert($data, $types);

            $lastInsertId = static::connection()->lastInsertId();

            if (!$lastInsertId) {
                $lastInsertId = static::connection()->lastInsertId(static::tableName() . '_id_seq');
            }

            if ($result) {
                $this->_isNew = false;
                $this->id     = $lastInsertId;
            }
        } else {
            $this->runFilters('before', 'save');

            $data = $this->getData();

            if (isset(static::schema()['updated_at'])) {
                $data['updated_at'] = new DateTime('now');
                $types['updated_at'] = 'datetime';
            }

            $this->runFilters('before', 'update');

            $result = static::connection()->update(
                static::tableName(),
                $data,
                ['id' => $this->id],
                $types
            );
        }

        $this->refetchRow();

        $this->runFilters('after', $wasNew ? 'create' : 'save');

        return true;
    }

    /**
     * Fetches the model data from the database.
     */
    public function refetchRow()
    {
        $data = static::where('id = :id')->setParameter('id', $this->id)->execute()->fetch();
        $this->set(static::convertToDataTypes($data));
    }

    /**
     * Delete the models row from the database.
     *
     * @return integer
     */
    public function delete()
    {
        return static::connection()->delete(static::tableName(), ['id' => $this->id]);
    }
}
