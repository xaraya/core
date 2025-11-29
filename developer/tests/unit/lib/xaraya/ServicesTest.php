<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Context\Context;
use Xaraya\Services\xar;

final class ServicesTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        xar::cache()->init();
        xar::db()->init();

        // Set context for core services here first - for $this->mod()->loadDbInfo(...) inside DD
        $context = new Context();
        xar::setServicesContext($context);
    }

    public function testSerializeStaticServicesClass(): void
    {
        $xar = xar::getServicesClass();
        $expected = $xar->mem();

        // this creates a different StaticServicesClass = not equal
        $serialized = serialize($xar);
        $unserialized = unserialize($serialized);
        $this->assertNotEquals($xar, $unserialized);

        // this creates a different MemoryService = not equal
        $result = $unserialized->mem();
        $this->assertNotEquals($expected, $result);
    }

    public function testSerializeWithServicesClass(): void
    {
        $query = new Query();
        // unserialize() will call this too
        $query->openconnection();

        // this creates an equivalent Query() = not same but equal
        $serialized = serialize($query);
        $unserialized = unserialize($serialized);
        $this->assertNotSame($query, $unserialized);
        $this->assertEquals($query, $unserialized);

        // this will use the same StaticServicesClass = same
        $expected = $query->getServicesClass();
        $result = $unserialized->getServicesClass();
        $this->assertSame($expected, $result);

        // this will use the same database connection = same
        $expected = $query->dbconn;
        $result = $unserialized->dbconn;
        $this->assertSame($expected, $result);
    }

    public function testSerializeModulesService(): void
    {
        $xar = xar::getServicesClass();
        // initialize mod()
        $xar->mod()->init();
        // get specialized mod() for dynanicdata
        $expected = $xar->mod('dynamicdata');

        // this creates an equivalent ModulesService() = not same but equal
        $serialized = serialize($expected);
        $unserialized = unserialize($serialized);
        $this->assertNotSame($expected, $unserialized);
        $this->assertEquals($expected, $unserialized);

        // this will return the same currentModName = same
        $expected = $expected->getModName();
        $result = $unserialized->getModName();
        $this->assertSame($expected, $result);
    }

    public function testSerializeWrapperService(): void
    {
        $xar = xar::getServicesClass();
        // initialize events()
        $xar->events()->init();
        $expected = $xar->events();

        // this creates an equivalent WrapperService() = not same and not equal, but same behaviour for closure
        $serialized = serialize($expected);
        $unserialized = unserialize($serialized);
        $this->assertNotSame($expected, $unserialized);
        $this->assertNotEquals($expected, $unserialized);

        // this will return the same subjects = same
        $expected = $expected->getSubjects();
        $result = $unserialized->getSubjects();
        $this->assertSame($expected, $result);
    }
}
