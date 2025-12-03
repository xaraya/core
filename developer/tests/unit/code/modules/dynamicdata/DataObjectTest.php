<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Services\xar;

final class DataObjectTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        xar::cache()->init();
        xar::db()->init();
    }

    public static function tearDownAfterClass(): void
    {
        xar::sysConfig(sys::LAYOUT)->setVar('BaseURI', null);
        // @todo reset deferred property caches after DataObjectTest
        DeferredItemProperty::$deferred = [];
        DeferredListProperty::$deferred = [];
        DeferredManyProperty::$deferred = [];
    }

    protected function getFixtureFile($name)
    {
        return __DIR__ . '/fixtures/' . $name;
    }

    public function testGetObjectList(): void
    {
        $params = ['name' => 'objects', 'fieldlist' => null];
        $objectlist = DataObjectFactory::getObjectList($params);
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
        $object = DataObjectFactory::getObject($params);
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
        $objectlist = DataObjectFactory::getObjectList($params);
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
        $object = DataObjectFactory::getObject($params);
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
        xar::ctl()->setBaseURL('http://localhost/');
        xar::req()->setServerVar('REQUEST_URI', '/index.php');

        // needed to initialize the template cache
        xar::tpl()->init();
        xar::block()->init();
        // needed for security checks later...
        xar::session()->setAnonId(xarConfigVars::get(null, 'Site.User.AnonymousUID', 5));
        // needed to check security for the view options
        xar::user()->init();

        $expected = '5';
        $this->assertEquals($expected, xar::session()->getAnonId());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPrepareOutput')]
    public function testShowView(): void
    {
        $params = ['name' => 'sample', 'fieldlist' => null, 'linktype' => 'object'];
        $objectlist = DataObjectFactory::getObjectList($params);
        $expected = 'Sample Object';
        $this->assertEquals($expected, $objectlist->label);

        $objectlist->getItems();
        $output = $objectlist->showView();
        $filename = $this->getFixtureFile('showview.sample.html');
        $expected = filesize($filename);
        $output = preg_replace('/<!--.*?-->/s', '', $output);
        $this->assertEquals($expected, strlen($output));
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPrepareOutput')]
    public function testObjectInterface(): void
    {
        $params = ['object' => 'sample', 'linktype' => 'object'];
        $context = new Xaraya\Context\Context(['source' => __METHOD__]);
        $interface = DataObjectFactory::getObjectInterface($params, $context);
        $expected = 'sample';
        $this->assertEquals($expected, $interface->args['object']);

        $output = $interface->handle();
        $filename = $this->getFixtureFile('ui_handlers.view.html');
        $expected = filesize($filename);
        $output = preg_replace('/<!--.*?-->/s', '', $output);
        $this->assertEquals($expected, strlen($output));
    }
}
