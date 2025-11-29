<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Context\Context;
use Xaraya\Services\xar;

final class SerializeServicesTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        xar::cache()->init();
        xar::db()->init();

        // Set context for core services here first - for $this->mod()->loadDbInfo(...) inside DD
        $context = new Context();
        xar::setServicesContext($context);
    }

    public function testStaticServicesClass(): void
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

    public function testWithServicesClass(): void
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

    public function testModulesService(): void
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

    public function testWrapperService(): void
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

    public function testCoreServicesTrait(): void
    {
        $xar = xar::getServicesClass();
        $dataobject = $xar->data()->getObject(['name' => 'sample']);
        $expected = $dataobject;

        // this creates an equivalent DataObject() = not same and not equal, but same behaviour for objects
        $serialized = serialize($expected);
        $unserialized = unserialize($serialized);
        $this->assertNotSame($expected, $unserialized);
        $this->assertNotEquals($expected, $unserialized);

        $this->assertNotSame($expected->descriptor, $unserialized->descriptor);
        $this->assertEquals($expected->descriptor, $unserialized->descriptor);

        // this will return the same itemid = same
        $expected = $dataobject->getItem(['itemid' => 1]);
        $result = $unserialized->getItem(['itemid' => 1]);
        $this->assertSame($expected, $result);

        // this will return the same item fields = same
        $expected = $dataobject->getFieldValues();
        $result = $unserialized->getFieldValues();
        $this->assertSame($expected, $result);
    }

    public function testParentServicesTrait(): void
    {
        $xar = xar::getServicesClass();
        $dataobject = $xar->data()->getObject(['name' => 'sample']);
        $dataobject->getItem(['itemid' => 1]);
        $dataproperty = $dataobject->properties['name'];
        $expected = $dataproperty;

        // this creates an equivalent DataProperty() = not same and not equal, but same behaviour for properties
        $serialized = serialize($expected);
        $unserialized = unserialize($serialized);
        $this->assertNotSame($expected, $unserialized);
        $this->assertNotEquals($expected, $unserialized);

        // this will return the same value = same
        $expected = $dataproperty->getValue();
        $result = $unserialized->getValue();
        $this->assertSame($expected, $result);

        // this will return an equivalent parent = not same and not equal, but same behaviour for objects
        $expected = $dataproperty->getParent();
        $result = $unserialized->getParent();
        $this->assertSame($expected, $dataobject);
        $this->assertNotSame($expected, $result);
        $this->assertNotEquals($expected, $result);

        $this->assertNotSame($expected->descriptor, $result->descriptor);
        $this->assertEquals($expected->descriptor, $result->descriptor);
    }
}
