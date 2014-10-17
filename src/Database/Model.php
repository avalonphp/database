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
use Avalon\Database;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/**
 * Avalon base model.
 *
 * @author Jack Polgar <jack@polgar.id.au>
 *
 * @MappedSuperclass
 */
class Model
{
    /**
     * @var string
     */
    protected static $tableName;

    /**
     * @var string
     */
    protected static $connectionName = Database::DEFAULT_CONNECTION;

    /**
     * @param integer $id
     */
    public static function find($id)
    {
        return static::getRepository()->find($id);
    }

    /**
     * @return array
     */
    public static function all()
    {
        return static::getRepository()->findAll();
    }

    /**
     * @return EntityManager
     */
    public static function getConnection()
    {
        return Database::connection(static::$connectionName);
    }

    public static function getRepository()
    {
        return static::getConnection()->getRepository(get_called_class());
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $metadata->setTableName(static::tableName());

        static::buildMetadata($metadata, $builder);
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        if (!isset(static::$tableName)) {
            $r = new ReflectionClass(new static);
            static::$tableName = strtolower($r->getShortName()) . 's';
        }

        return static::$tableName;
    }
}
