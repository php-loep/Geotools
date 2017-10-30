<?php

/*
 * This file is part of the Geotools library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace League\Getools\Tests\Cache;

use League\Geotools\Cache\Memcached;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class MemcachedTest extends \League\Geotools\Tests\TestCase
{
    protected $memcached;

    protected function setUp()
    {
        if (!extension_loaded('memcached')) {
            $this->markTestSkipped('You need to install Memcached.');
        }

        if (version_compare(phpversion('memcached'), '2.2.0', '>=')) {
            $this->markTestSkipped('Tests can only be run with memcached extension 2.1.0 or lower');
        }

        $this->memcached = new TestableMemcached;
    }

    public function testConstructor()
    {
        new Memcached;
    }

    public function testGetKey()
    {
        $key = $this->memcached->getKey('foo', 'bar');

        $this->assertTrue(is_string($key));
        $this->assertEquals('3858f62230ac3c915f300c664312c63f', $key);
    }

    public function testCache()
    {
        $mockMemcached = $this->getMock('\Memcached', array('set'));
        $mockMemcached
            ->expects($this->once())
            ->method('set');

        $this->memcached->setMemcached($mockMemcached);
        $this->memcached->cache($this->createMock('\League\Geotools\Batch\BatchGeocoded'));
    }

    public function testIsCachedReturnsFalse()
    {
        $mockMemcached = $this->getMockBuilder('\Memcached')
            ->disableOriginalConstructor()
            ->setMethods(array('get'))
            ->getMock();

        $mockMemcached
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue(false));

        $this->memcached->setMemcached($mockMemcached);
        $cached = $this->memcached->isCached('foo', 'bar');

        $this->assertFalse($cached);
    }

    public function testIsCachedReturnsBatchGeocodedObject()
    {
        $json = <<<JSON
{
    "providerName": "google_maps",
    "query": "Paris, France",
    "exceptionMessage": "",
    "coordinates": [48.856614, 2.3522219],
    "latitude": 48.856614,
    "longitude": 2.3522219,
    "address": {
        "latitude": 48.856614,
        "longitude": 2.3522219,
        "bounds": {
            "south": 48.815573,
            "west": 2.224199,
            "north": 48.9021449,
            "east": 2.4699208
        },
        "streetNumber": null,
        "streetName": null,
        "locality": "Paris",
        "postalCode": null,
        "subLocality": null,
        "adminLevels": {
            "1": {
                "level": 1,
                "name": "New York",
                "code": "NY"
            },
            "2": {
                "level": 2,
                "name": "New York County",
                "code": "New York County"
            }
        },
        "country": "France",
        "countryCode": "FR",
        "timezone": null
    }
}
JSON
        ;

        $mockMemcached = $this->getMockBuilder('\Memcached')
            ->disableOriginalConstructor()
            ->setMethods(array('get'))
            ->getMock();

        $mockMemcached
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue($json));

        $this->memcached->setMemcached($mockMemcached);
        $cached = $this->memcached->isCached('foo', 'bar');

        $this->assertTrue(is_object($cached));
        $this->assertInstanceOf('\League\Geotools\Batch\BatchGeocoded', $cached);
        $this->assertEquals('google_maps', $cached->getProviderName());
        $this->assertEquals('Paris, France', $cached->getQuery());
        $this->assertEmpty($cached->getExceptionMessage());
        $this->assertInstanceOf('\Geocoder\Model\Coordinates', $cached->getCoordinates());
        $this->assertEquals(48.856614, $cached->getLatitude());
        $this->assertEquals(2.3522219, $cached->getLongitude());
        $this->assertInstanceOf('\Geocoder\Model\Bounds', $cached->getBounds());
        $bounds = $cached->getBounds()->toArray();
        $this->assertTrue(is_array($bounds));
        $this->assertCount(4, $bounds);
        $this->assertEquals(48.815573, $bounds['south']);
        $this->assertEquals(2.224199, $bounds['west']);
        $this->assertEquals(48.9021449, $bounds['north']);
        $this->assertEquals(2.4699208, $bounds['east']);
        $this->assertNull($cached->getStreetNumber());
        $this->assertNull($cached->getStreetName());
        $this->assertEquals('Paris', $cached->getLocality());
        $this->assertNull($cached->getPostalCode());
        $this->assertNull($cached->getSubLocality());
        $this->assertInstanceOf('\Geocoder\Model\AdminLevelCollection', $cached->getAdminLevels());
        $adminLevels = $cached->getAdminLevels()->all();
        $this->assertTrue(is_array($adminLevels));
        $this->assertCount(2, $adminLevels);
        $this->assertInstanceOf('\Geocoder\Model\AdminLevel', $adminLevels[1]);
        $this->assertEquals('New York', $adminLevels[1]->getName());
        $this->assertEquals('NY', $adminLevels[1]->getCode());
        $this->assertInstanceOf('\Geocoder\Model\AdminLevel', $adminLevels[2]);
        $this->assertEquals('New York County', $adminLevels[2]->getName());
        $this->assertEquals('New York County', $adminLevels[2]->getCode());
        $this->assertEquals('France', $cached->getCountry()->toString());
        $this->assertEquals('FR', $cached->getCountryCode());
        $this->assertNull($cached->getTimezone());
    }

    public function testFlush()
    {
        $mockMemcached = $this->getMockBuilder('\Memcached')
            ->disableOriginalConstructor()
            ->setMethods(array('flush'))
            ->getMock();

        $mockMemcached
            ->expects($this->once())
            ->method('flush');

        $this->memcached->setMemcached($mockMemcached);
        $this->memcached->flush();
    }
}

class TestableMemcached extends Memcached
{
    public function setMemcached($memcached)
    {
        $this->memcached = $memcached;
    }
}
