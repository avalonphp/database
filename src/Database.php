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

namespace Avalon;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

/**
 * Database connection manager
 *
 * @author Jack Polgar <jack@polgar.id.au>
 */
class Database
{
    /**
     * Default connection name
     */
    const DEFAULT_CONNECTION = 'default';

    /**
     * @var array
     */
    protected static $connections = [];

    /**
     * @param array  $dbConfig   Database configuration
     * @param array  $modelPaths Paths of model directories
     * @param string $name       Connection name
     *
     * @return EntityManager
     */
    public static function connect(array $dbConfig, array $modelPaths = [], $name = Database::DEFAULT_CONNECTION)
    {
        $isDevMode = isset($dbConfig['devMode']) ? $dbConfig['devMode'] : false;

        $config = Setup::createAnnotationMetadataConfiguration($modelPaths, $isDevMode);
        $entityManager = EntityManager::create($dbConfig, $config);

        return static::$connections[$name] = $entityManager;
    }

    /**
     * @param string $name Connection name
     *
     * @return EntityManager
     */
    public static function connection($name = Database::DEFAULT_CONNECTION)
    {
        return static::$connections[$name];
    }
}
