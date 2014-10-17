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

namespace Avalon\Database\Mapping;

use Doctrine\ORM\Mapping\Driver\StaticPHPDriver as DoctrineStaticPHPDriver;

/**
 * {@inheritdoc}
 */
class StaticPHPDriver extends DoctrineStaticPHPDriver
{
    /**
     * {@inheritdoc}
     */
    public function isTransient($className)
    {
        if ($className === 'Avalon\Database\Model') {
            return true;
        }

        return parent::isTransient($className);
    }
}
