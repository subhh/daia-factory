<?php

/*
 * This file is part of DAIA Factory.
 *
 * DAIA Factory is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * DAIA Factory is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with DAIA Factory. If not, see <https://www.gnu.org/licenses/>.
 *
 * @author    David Maus <david.maus@sub.uni-hamburg.de>
 * @copyright (c) 2023 by Staats- und Universitätsbibliothek Hamburg
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */

declare(strict_types=1);

namespace SUBHH\DAIA\Factory;

use SUBHH\DAIA\Model;
use Swaggest\JsonSchema;

use PHPUnit\Framework\TestCase;

final class DAIAFactoryTest extends TestCase
{
    public function testValidResponse () : void
    {
        $schema = JsonSchema\Schema::import(json_decode(file_get_contents(__DIR__ . '/../../main/resources/daia.schema.json')));
        $factory = new DAIAFactory($schema);
    }
}
