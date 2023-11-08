<?php

use PHPUnit\Framework\TestCase;

final class VariableTableTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        xarCache::init();
        xarDatabase::init();
    }

    protected function getFixtureFile($name)
    {
        return __DIR__ . '/fixtures/' . $name;
    }

    protected function getFixtureData($name)
    {
        $filename = $this->getFixtureFile($name);
        $contents = file_get_contents($filename);
        return json_decode($contents, true);
    }

    protected function saveFixtureData($name, $data)
    {
        $filename = $this->getFixtureFile($name);
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
    }

    public function testGetItemsById(): void
    {
        $objectlist = DataObjectFactory::getObjectList(['name' => 'sample']);
        $items = $objectlist->getItems(['itemids' => [1, 3]]);
        //$this->saveFixtureData('getitemsbyid.json', $items);
        $expected = $this->getFixtureData('getitemsbyid.json');
        $this->assertEquals($expected, $items);
    }

    /**
    public function testGetItemsJoin(): void
    {
        $objectlist = DataObjectFactory::getObjectList(['name' => 'sample']);
        // @todo $objectlist->datastore->join = [];
        $items = $objectlist->getItems();
        //$this->saveFixtureData('getitemsjoin.json', $items);
        $expected = $this->getFixtureData('getitemsjoin.json');
        $this->assertEquals($expected, $items);
    }
     */

    public function testGetItemsLimit(): void
    {
        $objectlist = DataObjectFactory::getObjectList(['name' => 'sample']);
        $objectlist->numitems = 2;
        $items = $objectlist->getItems();
        //$this->saveFixtureData('getitemslimit.json', $items);
        $expected = $this->getFixtureData('getitemslimit.json');
        $this->assertEquals($expected, $items);
        // @todo test ddsort, ddwhere, groupby
    }

    /**
    public function testGetItemsProcess(): void
    {
        $objectlist = DataObjectFactory::getObjectList(['name' => 'sample']);
        $objectlist->properties['id']->operation = 'COUNT';
        $objectlist->properties['age']->operation = 'AVG';
        $items = $objectlist->getItems();
        $this->saveFixtureData('getitemsprocess.json', $items);
        $expected = $this->getFixtureData('getitemsprocess.json');
        $this->assertEquals($expected, $items);
    }
     */

    public function testGetItemsAll(): void
    {
        $objectlist = DataObjectFactory::getObjectList(['name' => 'sample']);
        $items = $objectlist->getItems();
        //$this->saveFixtureData('getitemsall.json', $items);
        $expected = $this->getFixtureData('getitemsall.json');
        $this->assertEquals($expected, $items);
    }

    /**
    public function testCountItemsById(): void
    {
        $objectlist = DataObjectFactory::getObjectList(['name' => 'sample']);
        $expected = 2;
        // @todo fix countItems with itemids
        //$this->assertEquals($expected, $objectlist->countItems(['itemids' => [1, 3]]));
    }
     */

    /**
    public function testCountItemsLimit(): void
    {
        $objectlist = DataObjectFactory::getObjectList(['name' => 'sample']);
        // @todo fix countItems with limits
        //$this->assertEquals($expected, $objectlist->countItems(['where' => [...]]));
    }
     */

    public function testCountItemsAll(): void
    {
        $objectlist = DataObjectFactory::getObjectList(['name' => 'sample']);
        $expected = 3;
        $this->assertEquals($expected, $objectlist->countItems());
    }
}
