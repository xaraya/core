<?php

use PHPUnit\Framework\TestCase;

final class DataObjectTest extends TestCase
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

    public function testGetObjectList(): void
    {
        $params = ['name' => 'objects', 'fieldlist' => null];
        $objectlist = DataObjectMaster::getObjectList($params);
        $expected = 'Dynamic Objects';
        $this->assertEquals($expected, $objectlist->label);

        $items = $objectlist->getItems();
        $expected = 19;
        $this->assertCount($expected, $items);
        $expected = 'Dynamic Objects';
        $this->assertEquals($expected, $items['1']['label']);
    }

    public function testGetObject(): void
    {
        $params = ['name' => 'objects', 'itemid' => 2, 'fieldlist' => null];
        $object = DataObjectMaster::getObject($params);
        $expected = 'Dynamic Objects';
        $this->assertEquals($expected, $object->label);

        $itemid = $object->getItem();
        $expected = 2;
        $this->assertEquals($expected, $itemid);
        $expected = 'Dynamic Properties';
        $this->assertEquals($expected, $object->properties['label']->getValue());
    }

    public function testGetSampleList(): void
    {
        $params = ['name' => 'sample', 'fieldlist' => null];
        $objectlist = DataObjectMaster::getObjectList($params);
        $expected = 'Sample Object';
        $this->assertEquals($expected, $objectlist->label);

        $items = $objectlist->getItems();
        $expected = 3;
        $this->assertCount($expected, $items);
        $expected = 'Johnny';
        $this->assertEquals($expected, $items['1']['name']);
    }

    public function testGetSample(): void
    {
        $params = ['name' => 'sample', 'itemid' => 2, 'fieldlist' => null];
        $object = DataObjectMaster::getObject($params);
        $expected = 'Sample Object';
        $this->assertEquals($expected, $object->label);

        $itemid = $object->getItem();
        $expected = 2;
        $this->assertEquals($expected, $itemid);
        $expected = 'Nancy';
        $this->assertEquals($expected, $object->properties['name']->getValue());
    }

    public function testPrepareOutput(): void
    {
        xarServer::setBaseURL('http://localhost/');
        xarServer::setVar('REQUEST_URI', '/index.php');

        // needed to initialize the template cache
        xarTpl::init();
        // needed for security checks later...
        xarSession::$anonId = xarConfigVars::get(null, 'Site.User.AnonymousUID', 5);
        //$_SESSION[xarSession::PREFIX . 'role_id'] = xarSession::$anonId;
        // needed to check security for the view options
        xarUser::init();

        $expected = '5';
        $this->assertEquals($expected, xarSession::$anonId);
    }

    /**
     * @depends testPrepareOutput
     */
    public function testShowView(): void
    {
        $params = ['name' => 'sample', 'fieldlist' => null];
        $objectlist = DataObjectMaster::getObjectList($params);
        $expected = 'Sample Object';
        $this->assertEquals($expected, $objectlist->label);

        $objectlist->getItems();
        $output = $objectlist->showView();
        $filename = $this->getFixtureFile('showview.sample.html');
        $expected = filesize($filename);
        $this->assertEquals($expected, strlen($output));
    }

    /**
     * @depends testPrepareOutput
     */
    public function testObjectInterface(): void
    {
        $params = ['object' => 'sample'];
        $interface = DataObjectMaster::getObjectInterface($params);
        $expected = 'sample';
        $this->assertEquals($expected, $interface->args['object']);

        $output = $interface->handle();
        $filename = $this->getFixtureFile('ui_handlers.view.html');
        $expected = filesize($filename);
        $this->assertEquals($expected, strlen($output));
    }
}
