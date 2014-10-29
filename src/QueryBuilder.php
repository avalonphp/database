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

use Doctrine\DBAL\Query\QueryBuilder as DoctrienQueryBuilder;

/**
 * Query Builder based on Doctrine's Query Builder to add support for fetching
 * data with a model.
 *
 * @author Jack Polgar <jack@polgar.id.au>
 */
class QueryBuilder extends DoctrienQueryBuilder
{
    /**
     * @var string
     */
    protected $modelClass;

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

        if ($data = $statement->fetch()) {
            return new $this->modelClass($data, false);
        } else {
            return $data;
        }
    }

    /**
     * @return \Avalon\Datbase\Model[]
     */
    public function fetchAll()
    {
        $statement = $this->execute();

        $rows = [];
        foreach ($statement->fetchAll() as $row) {
            $rows[] = new $this->modelClass($row, false);
        }

        return $rows;
    }

    /**
     * @param string  $predicates The restriction predicates.
     * @param mixed   $value      Value of the restriction.
     * @param integer $type       One of the PDO::PARAM_* constants.
     *
     * @return QueryBuilder
     */
    public function where($predicates, $value = null, $type = \PDO::PARAM_STR)
    {
        parent::where($predicates);

        if ($value !== null) {
            $this->_setParameter($predicates, $value, $type);
        }

        return $this;
    }

    /**
     * @param string  $predicates The restriction predicates.
     * @param mixed   $value      Value of the restriction.
     * @param integer $type       One of the PDO::PARAM_* constants.
     *
     * @return QueryBuilder
     */
    public function andWhere($predicates, $value = null, $type = \PDO::PARAM_STR)
    {
        parent::andWhere($predicates);

        if ($value !== null) {
            $this->_setParameter($predicates, $value, $type);
        }

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

     * @param string  $predicates The restriction predicates.
     * @param mixed   $value      Value of the restriction.
     * @param integer $type       One of the PDO::PARAM_* constants.
     */
    protected function _setParameter($predicates, $value, $type)
    {
        if (strpos($predicates, ':')) {
            preg_match("/(?P<placeholder>:[\w\d\_]+)/", $predicates, $matches);
            $placeholder = $matches['placeholder'];

            $this->createNamedParameter($value, $type, $placeholder);
        } else {
            $this->createPositionalParameter($value, $type);
        }
    }
}
