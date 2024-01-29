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
use Swaggest\JsonSchema;
use GuzzleHttp\Psr7\Uri;

use InvalidArgumentException;
use RuntimeException;
use DateInterval;
use DateTimeImmutable;
use stdClass;

final class DAIAFactory
{
    /** @var JsonSchema\SchemaContract */
    private $schema;

    public function __construct ()
    {
        $schemaUri = __DIR__ . '/../resources/daia.schema.json';
        $schemaContent = file_get_contents($schemaUri);
        if ($schemaContent === false) {
            throw new RuntimeException(
                sprintf('Unable to read DAIA schema specification: %s', $schemaUri)
            );
        }
        $this->schema = JsonSchema\Schema::import(json_decode($schemaContent));
    }

    public function createFromJson (string $json) : Model\DAIA
    {
        $data = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        if ($data instanceof stdClass) {
            return $this->createFromDecodedJson($data);
        }
        throw new InvalidArgumentException(
            sprintf('The encoded JSON string does not decode to an JSON object: %s', $json)
        );
    }

    public function createFromDecodedJson (stdClass $data) : Model\DAIA
    {
        $this->schema->in($data);
        $daia = new Model\DAIA();
        if (isset($data->timestamp)) {
            $daia->setTimestamp(new DateTimeImmutable($data->timestamp));
        }
        if (isset($data->institution)) {
            $daia->setInstitution($this->createInstitution($data->institution));
        }
        if (isset($data->document)) {
            foreach ($data->document as $document) {
                $daia->addDocument($this->createDocument($document));
            }
        }
        return $daia;
    }

    private function createDocument (stdClass $data) : Model\Document
    {
        $document = new Model\Document(new Uri($data->id));
        if (isset($data->href)) {
            $document->setHref(new Uri($data->href));
        }
        if (isset($data->requested)) {
            $document->setRequested($data->requested);
        }
        if (isset($data->about)) {
            $document->setAbout($data->about);
        }
        if (isset($data->item)) {
            foreach ($data->item as $item) {
                $document->addItem($this->createItem($item));
            }
        }
        return $document;
    }

    private function createItem (stdClass $data) : Model\Item
    {
        $item = new Model\Item();
        if (isset($data->id)) {
            $item->setId(new Uri($data->id));
        }
        if (isset($data->href)) {
            $item->setHref(new Uri($data->href));
        }
        if (isset($data->about)) {
            $item->setAbout($data->about);
        }
        if (isset($data->part)) {
            $item->setPart($data->part);
        }
        if (isset($data->department)) {
            $item->setDepartment($this->createDepartment($data->department));
        }
        if (isset($data->storage)) {
            $item->setStorage($this->createStorage($data->storage));
        }
        if (isset($data->chronology)) {
            $item->setChronology($this->createChronology($data->chronology));
        }
        if (isset($data->label)) {
            $item->setLabel($data->label);
        }
        if (isset($data->available)) {
            foreach ($data->available as $available) {
                $item->addAvailable($this->createAvailable($available));
            }
        }
        if (isset($data->unavailable)) {
            foreach ($data->unavailable as $unavailable) {
                $item->addUnavailable($this->createUnavailable($unavailable));
            }
        }

        return $item;
    }

    private function createUnavailable (stdClass $data) : Model\Unavailable
    {
        $service = $this->createService($data->service);
        $unavailable = new Model\Unavailable($service);
        if (isset($data->queue)) {
            $unavailable->setQueue($data->queue);
        }
        if (isset($data->expected)) {
            if ($data->expected === 'unknown') {
                $unavailable->setExpectedUnknown();
            } else {
                $unavailable->setExpected(new DateTimeImmutable($data->expected));
            }
        }
        $this->initializeAvailability($unavailable, $data);
        return $unavailable;
    }

    private function createAvailable (stdClass $data) : Model\Available
    {
        $service = $this->createService($data->service);
        $available = new Model\Available($service);
        if (isset($data->delay)) {
            if ($data->delay === 'unknown') {
                $available->setDelayUnknown();
            } else {
                $available->setDelay(new DateInterval($data->delay));
            }
        }
        $this->initializeAvailability($available, $data);
        return $available;
    }

    private function createChronology (stdClass $data) : Model\Chronology
    {
        $chronology = new Model\Chronology();
        if (isset($data->about)) {
            $chronology->setAbout($data->about);
        }
        return $chronology;
    }

    private function createInstitution (stdClass $data) : Model\Institution
    {
        $institution = new Model\Institution();
        $this->initializeEntity($institution, $data);
        return $institution;
    }

    private function createStorage (stdClass $data) : Model\Storage
    {
        $storage = new Model\Storage();
        $this->initializeEntity($storage, $data);
        return $storage;
    }

    private function createDepartment (stdClass $data) : Model\Department
    {
        $department = new Model\Department();
        $this->initializeEntity($department, $data);
        return $department;
    }

    private function createLimitation (stdClass $data) : Model\Limitation
    {
        $limitation = new Model\Limitation();
        $this->initializeEntity($limitation, $data);
        return $limitation;
    }

    private function initializeAvailability (Model\Availability $availability, stdClass $data) : void
    {
        if (isset($data->href)) {
            $availability->setHref(new Uri($data->href));
        }
        if (isset($data->title)) {
            $availability->setTitle($data->title);
        }
        if (isset($data->limitation)) {
            foreach ($data->limitation as $limitation) {
                $availability->addLimitation($this->createLimitation($limitation));
            }
        }
    }

    private function initializeEntity (Model\Entity $entity, stdClass $data) : void
    {
        if (isset($data->id)) {
            $entity->setId(new Uri($data->id));
        }
        if (isset($data->href)) {
            $entity->setHref(new Uri($data->href));
        }
        if (isset($data->content)) {
            $entity->setContent($data->content);
        }

    }

    private function createService (string $service) : Model\Service
    {
        return new Model\Service($this->createServiceUri($service));
    }

    private function createServiceUri (string $service) : Uri
    {
        if (in_array($service, ['presentation', 'loan', 'interloan', 'remote', 'openaccess'], true)) {
            return new Uri('http://purl.org/ontology/dso#' . ucfirst($service));
        } else {
            return new Uri($service);
        }
    }
}
