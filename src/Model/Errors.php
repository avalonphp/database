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

namespace Avalon\Database\Model;

use Avalon\Language;

/**
 * Errors model trait.
 *
 * @author Jack P.
 */
trait Errors
{
    /**
     * Returns the errors array.
     *
     * @return array
     */
    public function errors()
    {
        return $this->_errors;
    }

    /**
     * Check if the model has errors.
     *
     * @return boolean
     */
    public function hasErrors()
    {
        return count($this->_errors) > 0 ? true : false;
    }

    /**
     * Check if a field has an error.
     *
     * @param string $field
     *
     * @return boolean
     */
    public function hasError($field)
    {
        if (isset($this->_errors[$field])) {
            return true;
        }

        return false;
    }

    /**
     * Get error(s) for a field.
     *
     * @param string $field
     *
     * @return array|null
     */
    public function getError($field)
    {
        if (isset($this->_errors[$field])) {
            return $this->_errors[$field];
        }
    }

    /**
     * Get the error message(s) for a field.
     *
     * @param string $field
     *
     * @return array
     */
    public function getErrorMessage($field)
    {
        return isset($this->_errors[$field]) ? $this->_errors[$field] : [];
    }

    /**
     * Adds an error for the specified field.
     *
     * @param string $field
     * @param mixed  $data
     * @param string $index
     */
    public function addError($field, $data, $index = null)
    {
        if (!isset($this->_errors[$field])) {
            $this->_errors[$field] = [];
        }

        if (is_string($index)) {
            $this->_errors[$field][$index] = $data;
        } else {
            $this->_errors[$field][] = $data;
        }
    }

    /**
     * Adds a validation error for the specified field.
     *
     * @param string $field
     * @param mixed  $data
     * @param string $index
     */
    public function addValidationError($field, $validation, array $options = [], $index = null)
    {
        $this->addError(
            $field,
            Language::translate("errors.validations.{$validation}", ['field' => Language::translate($field)] + $options)
        );
    }
}
