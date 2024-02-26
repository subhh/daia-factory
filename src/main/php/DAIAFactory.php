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

class DAIAFactory
{
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

    /** @param stdClass|mixed[] $data */
    public function createFromDecodedJson (stdClass|array $data) : Model\DAIA
    {
        $daia = new Model\DAIA();
        $props = is_array($data) ? $data : get_object_vars($data);
        foreach ($props as $name => $value) {
            switch ($name) {
            case 'timestamp':
                $daia->setTimestamp(new DateTimeImmutable($value));
                break;
            case 'institution':
                $daia->setInstitution($this->createInstitution($value));
                break;
            case 'document':
                foreach ($value as $document) {
                    $daia->addDocument($this->createDocument($document));
                }
                break;
            default:
                $daia->getProperties()[$name] = $value;
            }
        }
        return $daia;
    }

    /** @param stdClass|mixed[] $data */
    public function createDocument (stdClass|array $data) : Model\Document
    {
        $props = is_array($data) ? $data : get_object_vars($data);
        if (!array_key_exists('id', $props)) {
            throw new InvalidArgumentException("The required property 'id' is missing");
        }
        $document = new Model\Document(new Uri($props['id']));
        foreach ($props as $name => $value) {
            switch ($name) {
            case 'id':
                break;
            case 'href':
                $document->setHref(new Uri($value));
                break;
            case 'requested':
                $document->setRequested($value);
                break;
            case 'about':
                $document->setAbout($value);
                break;
            case 'item':
                foreach ($value as $item) {
                    $document->addItem($this->createItem($item));
                }
                break;
            default:
                $document->getProperties()[$name] = $value;
            }
        }
        return $document;
    }

    /** @param stdClass|mixed[] $data */
    public function createItem (stdClass|array $data) : Model\Item
    {
        $item = new Model\Item();
        $props = is_array($data) ? $data : get_object_vars($data);
        foreach ($props as $name => $value) {
            switch ($name) {
            case 'id':
                $item->setId(new Uri($value));
                break;
            case 'about':
                $item->setAbout($value);
                break;
            case 'part':
                $item->setPart($value);
                break;
            case 'href':
                $item->setHref(new Uri($value));
                break;
            case 'department':
                $item->setDepartment($this->createDepartment($value));
                break;
            case 'storage':
                $item->setStorage($this->createStorage($value));
                break;
            case 'chronology':
                $item->setChronology($this->createChronology($value));
                break;
            case 'label':
                $item->setLabel($value);
                break;
            case 'available':
                foreach ($value as $available) {
                    $item->addAvailable($this->createAvailable($available));
                }
                break;
            case 'unavailable':
                foreach ($value as $unavailable) {
                    $item->addUnavailable($this->createUnavailable($unavailable));
                }
                break;
            default:
                $item->getProperties()[$name] = $value;
            }
        }
        return $item;
    }

    /** @param stdClass|mixed[] $data */
    public function createUnavailable (stdClass|array $data) : Model\Unavailable
    {
        $props = is_array($data) ? $data : get_object_vars($data);
        if (!array_key_exists('service', $props)) {
            throw new InvalidArgumentException("The required property 'service' is missing");
        }

        $service = $this->createService($props['service']);
        $unavailable = new Model\Unavailable($service);
        foreach ($props as $name => $value) {
            switch ($name) {
            case 'service':
                break;
            case 'queue':
                $unavailable->setQueue($value);
                break;
            case 'expected':
                if ($value === 'unknown') {
                    $unavailable->setExpectedUnknown();
                } else {
                    $unavailable->setExpected(new DateTimeImmutable($value));
                }
                break;
            case 'href':
                $unavailable->setHref(new Uri($value));
                break;
            case 'title':
                $unavailable->setTitle($value);
                break;
            case 'limitation':
                foreach ($value as $limitation) {
                    $unavailable->addLimitation($this->createLimitation($limitation));
                }
                break;
            default:
                $unavailable->getProperties()[$name] = $value;
            }
        }
        return $unavailable;
    }

    /** @param stdClass|mixed[] $data */
    public function createAvailable (stdClass|array $data) : Model\Available
    {
        $props = is_array($data) ? $data : get_object_vars($data);
        if (!array_key_exists('service', $props)) {
            throw new InvalidArgumentException("The required property 'service' is missing");
        }

        $service = $this->createService($props['service']);
        $available = new Model\Available($service);
        foreach ($props as $name => $value) {
            switch ($name) {
            case 'service':
                break;
            case 'delay':
                if ($value === 'unknown') {
                    $available->setDelayUnknown();
                } else {
                    $available->setDelay(new DateInterval($value));
                }
                break;
            case 'href':
                $available->setHref(new Uri($value));
                break;
            case 'title':
                $available->setTitle($value);
                break;
            case 'limitation':
                foreach ($value as $limitation) {
                    $available->addLimitation($this->createLimitation($limitation));
                }
                break;
            default:
                $available->getProperties()[$name] = $value;
            }
        }
        return $available;
    }

    /** @param stdClass|mixed[] $data */
    public function createChronology (stdClass|array $data) : Model\Chronology
    {
        $props = is_array($data) ? $data : get_object_vars($data);
        $chronology = new Model\Chronology();
        if (isset($props['about'])) {
            $chronology->setAbout($props['about']);
        }
        return $chronology;
    }

    /** @param stdClass|mixed[] $data */
    public function createInstitution (stdClass|array $data) : Model\Institution
    {
        $institution = new Model\Institution();
        $this->initializeEntity($institution, $data);
        return $institution;
    }

    /** @param stdClass|mixed[] $data */
    public function createStorage (stdClass|array $data) : Model\Storage
    {
        $storage = new Model\Storage();
        $this->initializeEntity($storage, $data);
        return $storage;
    }

    /** @param stdClass|mixed[] $data */
    public function createDepartment (stdClass|array $data) : Model\Department
    {
        $department = new Model\Department();
        $this->initializeEntity($department, $data);
        return $department;
    }

    /** @param stdClass|mixed[] $data */
    public function createLimitation (stdClass|array $data) : Model\Limitation
    {
        $limitation = new Model\Limitation();
        $this->initializeEntity($limitation, $data);
        return $limitation;
    }

    /** @param stdClass|mixed[] $data */
    private function initializeEntity (Model\Entity $entity, stdClass|array $data) : void
    {
        $props = is_array($data) ? $data : get_object_vars($data);
        foreach ($props as $name => $value) {
            switch ($name) {
            case 'id':
                $entity->setId(new Uri($value));
                break;
            case 'href':
                $entity->setHref(new Uri($value));
                break;
            case 'content':
                $entity->setContent($value);
                break;
            }
        }
    }

    private function createService (string $service) : Uri
    {
        if (in_array($service, ['presentation', 'loan', 'interloan', 'remote', 'openaccess'], true)) {
            return new Uri('http://purl.org/ontology/dso#' . ucfirst($service));
        } else {
            return new Uri($service);
        }
    }
}
