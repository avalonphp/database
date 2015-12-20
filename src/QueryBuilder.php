<?php
/*
 * Avalon
 * Copyright 2011-2015 Jack P.
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

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;

/**
 * Query Builder based on Doctrine's Query Builder to add support for fetching
 * data with a model.
 *
 * @author Jack P.
 */
class QueryBuilder extends DoctrineQueryBuilder
{
    /**
     * @var string
     */
    protected $modelClass;

    /**
     * @var bool
     */
    protected $mergeNextWhere = false;

    /**
     * @param string $class
     */
    public function setModel($class)
    {
        $this->modelClass = $class;
        return $this;
    }

    /**
     * @return \Avalon\Datbase\Model
     */
    public function fetch()
    {
        $statement = $this->execute();

        $class = $this->modelClass;
        if ($data = $statement->fetch()) {
            return new $class($data, false);
        } else {
            return $data;
        }
    }

    /**
     * @return \Avalon\Database\Model[]
     */
    public function fetchAll()
    {
        $statement = $this->execute();

        $rows = [];
        $class = $this->modelClass;
        foreach ($statement->fetchAll() as $row) {
            $rows[] = new $class($row, false);
        }

        return $rows;
    }

    /**
     * @param string  $predicates The restriction predicates.
     *
     * @return QueryBuilder
     */
    public function where($predicates)
    {
        if ($this->mergeNextWhere) {
            $this->mergeNextWhere = false;
            parent::andWhere($predicates);
        } else {
            parent::where($predicates);
        }

        return $this;
    }

    /**
     * @param string  $predicates The restriction predicates.
     *
     * @return QueryBuilder
     */
    public function andWhere($predicates)
    {
        parent::andWhere($predicates);
        return $this;
    }

    /**
     * Returns the number of rows found.
     *
     * @return integer
     */
    public function rowCount()
    {
        return $this->execute()->rowCount();
    }

    /**
     * Turn the next `where()` into `andWhere()`.
     *
     * @return QueryBuilder
     */
    public function mergeNextWhere()
    {
        $this->mergeNextWhere = true;
        return $this;
    }

    /**
     * Quote the passed string or strings in array.
     *
     * @param mixed $string
     *
     * @return mixed
     */
    public function quote($string)
    {
        if (is_array($string)) {
            foreach ($string as $key => $value) {
                $string[$key] = $this->quote($value);
            }

            return $string;
        } else {
            return $this->getConnection()->quote($string);
        }
    }
}
