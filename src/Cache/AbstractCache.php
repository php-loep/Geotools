<?php

/*
 * This file is part of the Geotools library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace League\Geotools\Cache;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Cache abstract class.
 *
 * Antoine Corcy <contact@sbin.dk>
 */
abstract class AbstractCache
{
    /**
     * Normalize an object to array.
     *
     * @param BatchGeocoded $object The BatchGeocoded object to normalize.
     *
     * @return array The normalized object.
     */
    protected function normalize($object)
    {
        $serializer = new Serializer(array(new GetSetMethodNormalizer), array());

        return $serializer->normalize($object);
    }

    /**
     * Serialize an object to json.
     *
     * @param BatchGeocoded $object The BatchGeocoded object to serialize.
     *
     * @return string The serialized object in json.
     */
    protected function serialize($object)
    {
        $serializer = new Serializer(array(new GetSetMethodNormalizer), array(new JsonEncoder));

        return $serializer->serialize($object, 'json');
    }

    /**
     * Deserialize a json to BatchGeocoded object.
     *
     * @param string $json The json string to deserialize to BatchGeocoded object.
     *
     * @return array The deserialized json to array.
     */
    protected function deserialize($json)
    {
        $array = json_decode($json, true);
        $array = $this->setAdminLevels($array);

        return $array;
    }

    /**
     * Set admin levels in the array for compatibility with geocoder
     *
     * @param $array
     */
    protected function setAdminLevels($array)
    {
        foreach ($array['address']['adminLevels'] as $level => &$adminLevel) {
            $adminLevel['level'] = $level;
        }

        return $array;
    }
}
