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

namespace Avalon\Database\Model;

use Avalon\Database\Validations;

/**
 * Model validations trait.
 *
 * @author Jack Polgar <jack@polgar.id.au>
 */
trait Validatable
{
    use Errors;

    /**
     * Validates the model data.
     *
     * @param array $data
     *
     * @return boolean
     */
    public function validates($data = null)
    {
        // Get data if it wasn't passed
        if ($data === null) {
            $data = $this->getData();
        }

        foreach (static::$_validates as $field => $validations) {
            Validations::run($this, $field, $validations);
        }

        return count($this->_errors) == 0;
    }
}
