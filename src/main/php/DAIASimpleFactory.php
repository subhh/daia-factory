<?php

/*
 * This file is part of DAIA Factory.
 *
 * DAIA Model is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * DAIA Model is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with DAIA Model. If not, see <https://www.gnu.org/licenses/>.
 *
 * @author    David Maus <dmaus@dmaus.name>
 * @copyright (c) 2023 by Staats- und Universitätsbibliothek Hamburg
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */

declare(strict_types=1);

namespace SUBHH\DAIA\Factory;

use SUBHH\DAIA\Model;
use GuzzleHttp\Psr7\Uri;

use InvalidArgumentException;
use DateInterval;
use DateTimeImmutable;
use stdClass;

final class DAIASimpleFactory
{

    public function createFromJson (string $json) : Model\DAIASimple
    {
        $data = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        if ($data instanceof stdClass) {
            return $this->createFromDecodedJson($data);
        }
        throw new InvalidArgumentException(
            sprintf('The encoded JSON string does not decode to an JSON object: %s', $json)
        );
    }

    public function createFromDecodedJson (stdClass $data) : Model\DAIASimple
    {
        if ($data->available) {
            $daia = new Model\DAIASimpleAvailable($data->service);
            if (isset($data->delay)) {
                if ($data->delay === 'unknown') {
                    $daia->setDelayUnknown();
                } else {
                    $daia->setDelay(new DateInterval($data->delay));
                }
            }
        } else {
            $daia = new Model\DAIASimpleUnavailable($data->service);
            if (isset($data->expected)) {
                if ($data->expected === 'unknown') {
                    $daia->setExpectedUnknown();
                } else {
                    $daia->setExpected(new DateTimeImmutable($data->expected));
                }
            }
            if (isset($data->queue)) {
                $daia->setQueue($data->queue);
            }

        }
        $this->initializeDAIASimple($daia, $data);
        return $daia;
    }

    private function initializeDAIASimple (Model\DAIASimple $daia, stdClass $data) : void
    {
        if (isset($data->href)) {
            $daia->setHref(new Uri($data->href));
        }
        if (isset($data->limitation)) {
            $daia->setLimitation($data->limitation);
        }
    }
}
