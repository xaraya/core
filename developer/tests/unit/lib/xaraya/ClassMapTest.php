<?php

use PHPUnit\Framework\TestCase;

final class ClassMapTest extends TestCase
{
    public function testGetClassType(): void
    {
        $classType = 'classes';
        $classes = xarClassMap::getClassType($classType);

        $expected = [
            'authsystem' => [
                'authtoken' => [
                    'Xaraya\Authentication\AuthToken' => sys::code() . 'modules/authsystem/class/authtoken.php',
                ],
            ],
        ];
        $modName = array_key_first($expected);
        $fileType = array_key_first($expected[$modName]);
        $this->assertGreaterThan(1, count($classes));
        $this->assertEquals($expected[$modName][$fileType], $classes[$modName][$fileType]);
    }

    public function testGetBlocks(): void
    {
        $blocks = xarClassMap::getBlocks();

        $expected = [
            'classname' => 'Authsystem_LoginBlock',
            'filepath' => '/html/code/modules/authsystem/xarblocks/login.php',
        ];
        $this->assertArrayHasKey($expected['classname'], $blocks);
        $this->assertStringEndsWith($expected['filepath'], $blocks[$expected['classname']]);
    }

    public function testFindBlock(): void
    {
        $modName = 'base';
        $type = 'menu';
        $blocks = xarClassMap::getBlocks($modName, $type);

        // we have three block classes here
        $expected = [
            'Base_MenuBlock' => sys::code() . 'modules/base/xarblocks/menu.php',
            'Base_MenuBlockConfig' => sys::code() . 'modules/base/xarblocks/menu_config.php',
            'Base_MenuBlockDisplay' => sys::code() . 'modules/base/xarblocks/menu_display.php',
        ];
        $this->assertEquals($expected, $blocks);

        // find the block class for display
        $interface = 'display';
        $result = xarClassMap::findBlock($modName, $type, $interface);

        $expected = [
            'classname' => 'Base_MenuBlockDisplay',
            'filepath' => sys::code() . 'modules/base/xarblocks/menu_display.php',
            'module' => 'base',
            'type' => 'menu',
            'interface' => 'display',
        ];
        $this->assertEquals($expected, $result);

        // we can't instantiate block instance without database here
        $defaults = get_class_vars($result['classname']);

        $expected = [
            'backlabel' => 'View Back End',
        ];
        $key = array_key_first($expected);
        $this->assertArrayHasKey($key, $defaults);
        $this->assertEquals($expected[$key], $defaults[$key]);
    }

    public function testFindBlockByPath(): void
    {
        $paths = [
            sys::code() . 'modules/base/xarblocks/menu/menu_display.php',
            sys::code() . 'modules/base/xarblocks/menu/display.php',
            sys::code() . 'modules/base/xarblocks/menu/menu.php',
            sys::code() . 'modules/base/xarblocks/menu_display.php',
            sys::code() . 'modules/base/xarblocks/menu.php',
        ];
        $result = xarClassMap::findBlockByPath($paths);

        $expected = [
            'filepath' => sys::code() . 'modules/base/xarblocks/menu_display.php',
            'found' => ['Base_MenuBlockDisplay' => sys::code() . 'modules/base/xarblocks/menu_display.php'],
        ];
        $this->assertEquals($expected, $result);

        // we can't instantiate block instance without database here
        $classname = array_key_first($result['found']);
        $defaults = get_class_vars($classname);

        $expected = [
            'marker' => '[x]',
            'showlogout' => true,
            'logoutlabel' => 'Logout',
            'logouttitle' => 'Logout from the site',
            'showback' => true,
            'backlabel' => 'View Back End',
            'backtitle' => 'View the site back end interface',
            'displayrss' => false,
            'rsslabel' => 'Syndication',
            'rsstitle' => 'Syndicate this content',
            'displayprint' => false,
            'printlabel' => 'Print View',
            'printtitle' => 'Printer friendly view of this page',
            'userlinks' => [],
            'links_default' => [
                [
                    'id' => 0,
                    'name' => 'Documentation',
                    'url' => '[base]&page=docs',
                    'label' => 'Documentation',
                    'title' => 'General Documentation',
                    'visible' => 0,
                    'menulinks' => [],
                ],
                [
                    'id' => 1,
                    'name' => 'eventsystem',
                    'url' => '[base]page=events',
                    'label' => 'Event System',
                    'title' => 'Event Messaging System Overview',
                    'visible' => 0,
                    'menulinks' => [],
                ],
            ],
            'modulelist' => [],
            'thismodname' => null,
            'thismodtype' => null,
            'thisfuncname' => null,
            'currenturl' => null,
            'truecurrenturl' => null,
        ];
        $this->assertEquals($expected, $defaults);
    }

    public function testFindBlockInvalid(): void
    {
        $paths = [
            sys::code() . 'modules/invalid/xarblocks/menu/menu_display.php',
            sys::code() . 'modules/invalid/xarblocks/menu/display.php',
            sys::code() . 'modules/invalid/xarblocks/menu/menu.php',
            sys::code() . 'modules/invalid/xarblocks/menu_display.php',
            sys::code() . 'modules/invalid/xarblocks/menu.php',
        ];
        $result = xarClassMap::findBlockByPath($paths);

        $expected = [
            'filepath' => '',
            'found' => [],
        ];
        $this->assertEquals($expected, $result);
    }

    public function testGetEventSubjects(): void
    {
        $subjects = xarClassMap::getEventSubjects();

        $expected = [
            'BaseServerRequestSubject' => sys::code() . 'modules/base/class/eventsubjects/serverrequest.php',
        ];
        $classname = array_key_first($expected);
        $this->assertArrayHasKey($classname, $subjects);
        $this->assertEquals($expected[$classname], $subjects[$classname]);

        $modName = 'base';
        $subjects = xarClassMap::getEventSubjects($modName, null);
        $this->assertArrayHasKey($classname, $subjects);
        $this->assertEquals($expected[$classname], $subjects[$classname]);
        $this->assertCount(3, $subjects);

        $event = 'ServerRequest';
        $subjects = xarClassMap::getEventSubjects(null, $event);
        $this->assertArrayHasKey($classname, $subjects);
        $this->assertEquals($expected[$classname], $subjects[$classname]);
        $this->assertCount(1, $subjects);

        $subjects = xarClassMap::getEventSubjects($modName, $event);
        $this->assertArrayHasKey($classname, $subjects);
        $this->assertEquals($expected[$classname], $subjects[$classname]);
        $this->assertCount(1, $subjects);

        // we can instantiate eventsubject instance without database (but without observers to notify)
        $item = ['module' => 'dynamicdata', 'itemtype' => 4, 'itemid' => 1];
        $instance = new $classname($item);
        $this->assertInstanceOf($classname, $instance);

        $expected = $event;
        $this->assertEquals($expected, $instance->getSubject());

        $expected = $item;
        $this->assertEquals($expected, $instance->getArgs());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testGetHookSubjects(): void
    {
        $subjects = xarClassMap::getHookSubjects();

        $expected = [
            'ModulesItemCreateSubject' => sys::code() . 'modules/modules/class/hooksubjects/itemcreate.php',
        ];
        $classname = array_key_first($expected);
        $this->assertArrayHasKey($classname, $subjects);
        $this->assertEquals($expected[$classname], $subjects[$classname]);
        $this->assertGreaterThan(22, count($subjects));

        $modName = 'modules';
        $subjects = xarClassMap::getHookSubjects($modName, null);
        $this->assertArrayHasKey($classname, $subjects);
        $this->assertEquals($expected[$classname], $subjects[$classname]);
        $this->assertCount(22, $subjects);

        $event = 'ItemCreate';
        $subjects = xarClassMap::getHookSubjects(null, $event);
        $this->assertArrayHasKey($classname, $subjects);
        $this->assertEquals($expected[$classname], $subjects[$classname]);
        $this->assertGreaterThan(0, count($subjects));

        $subjects = xarClassMap::getHookSubjects($modName, $event);
        $this->assertArrayHasKey($classname, $subjects);
        $this->assertEquals($expected[$classname], $subjects[$classname]);
        $this->assertCount(1, $subjects);

        //$this->markTestIncomplete('Risky: Test code or tested code did not remove its own error handlers');
        // we can't instantiate hooksubject instance without database here due to xarMod::getRegID($module)
        //$expected = 'No connection available';
        //$this->expectExceptionMessage($expected);

        // we can instantiate hooksubject instance without database here thanks to xarMod3::getRegID($module)
        $item = ['module' => 'dynamicdata', 'itemtype' => 4, 'itemid' => 1];
        $instance = new $classname($item);
        $this->assertInstanceOf($classname, $instance);

        $expected = $event;
        $this->assertEquals($expected, $instance->getSubject());

        $expected = $item;
        $expected['module_id'] = 182;
        $this->assertEquals($expected, $instance->getExtrainfo());

        // Note: error & exception handlers are set when importing xaraya.exceptions in xarCore for xarLog::message()
        //restore_error_handler();
        //restore_exception_handler();
    }

    public function testGetEventObservers(): void
    {
        $observers = xarClassMap::getEventObservers();

        $expected = [
            'ModulesModActivateObserver' => sys::code() . 'modules/modules/class/eventobservers/modactivate.php',
        ];
        $classname = array_key_first($expected);
        $this->assertArrayHasKey($classname, $observers);
        $this->assertEquals($expected[$classname], $observers[$classname]);

        $modName = 'modules';
        $observers = xarClassMap::getEventObservers($modName, null);
        $this->assertArrayHasKey($classname, $observers);
        $this->assertEquals($expected[$classname], $observers[$classname]);
        $this->assertCount(4, $observers);

        $event = 'ModActivate';
        $observers = xarClassMap::getEventObservers(null, $event);
        $this->assertArrayHasKey($classname, $observers);
        $this->assertEquals($expected[$classname], $observers[$classname]);
        $this->assertCount(2, $observers);

        $observers = xarClassMap::getEventObservers($modName, $event);
        $this->assertArrayHasKey($classname, $observers);
        $this->assertEquals($expected[$classname], $observers[$classname]);
        $this->assertCount(1, $observers);

        // we can instantiate eventobserver instance without database (but not run notify)
        $instance = new $classname();
        $this->assertInstanceOf($classname, $instance);

        $expected = $modName;
        $this->assertEquals($expected, $instance->module);
    }

    public function testGetHookObservers(): void
    {
        $observers = xarClassMap::getHookObservers();

        $expected = [
            'Xaraya\DataObject\HookObservers\ItemCreate' => sys::code() . 'modules/dynamicdata/class/hookobservers/itemcreate.php',
        ];
        $classname = array_key_first($expected);
        $this->assertArrayHasKey($classname, $observers);
        $this->assertEquals($expected[$classname], $observers[$classname]);
        $this->assertGreaterThan(10, count($observers));

        $modName = 'dynamicdata';
        $observers = xarClassMap::getHookObservers($modName, null);
        $this->assertArrayHasKey($classname, $observers);
        $this->assertEquals($expected[$classname], $observers[$classname]);
        $this->assertCount(10, $observers);

        $event = 'ItemCreate';
        $observers = xarClassMap::getHookObservers(null, $event);
        $this->assertArrayHasKey($classname, $observers);
        $this->assertEquals($expected[$classname], $observers[$classname]);
        $this->assertGreaterThan(0, count($observers));

        $observers = xarClassMap::getHookObservers($modName, $event);
        $this->assertArrayHasKey($classname, $observers);
        $this->assertEquals($expected[$classname], $observers[$classname]);
        $this->assertCount(1, $observers);

        // we can instantiate hookobserver instance without database (but not run notify)
        $instance = new $classname();
        $this->assertInstanceOf($classname, $instance);

        $expected = $modName;
        $this->assertEquals($expected, $instance->getModName());
    }

    public function testFindClassFile(): void
    {
        $type = 'eventsubjects';
        $modName = 'base';
        $event = 'ServerRequest';
        $result = xarClassMap::findClassFile($type, $modName, $event);

        $expected = [
            'classname' => 'BaseServerRequestSubject',
            'filepath' => sys::code() . 'modules/base/class/eventsubjects/serverrequest.php',
            'classtype' => $type,
            'module' => $modName,
            'filetype' => $event,
        ];
        $this->assertEquals($expected, $result);

        // we can instantiate eventsubject instance without database (but without observers to notify)
        $item = ['module' => 'dynamicdata', 'itemtype' => 4, 'itemid' => 1];
        $instance = new $result['classname']($item);
        $this->assertInstanceOf($result['classname'], $instance);

        $expected = $event;
        $this->assertEquals($expected, $instance->getSubject());

        $expected = $item;
        $this->assertEquals($expected, $instance->getArgs());
    }

    public function testFindHookObserver(): void
    {
        $modName = 'dynamicdata';
        $event = 'ItemCreate';
        $result = xarClassMap::findHookObserver($modName, $event);

        $expected = [
            'classname' => 'Xaraya\DataObject\HookObservers\ItemCreate',
            'filepath' => sys::code() . 'modules/dynamicdata/class/hookobservers/itemcreate.php',
            'classtype' => 'hookobservers',
            'module' => $modName,
            'filetype' => $event,
        ];
        $this->assertEquals($expected, $result);

        // we can instantiate hookobserver instance without database (but not run notify)
        $instance = new $result['classname']();
        $this->assertInstanceOf($expected['classname'], $instance);

        $expected = $modName;
        $this->assertEquals($expected, $instance->getModName());
    }

    public function testGetDataObjects(): void
    {
        $dataobjects = xarClassMap::getDataObjects();

        $expected = [
            'Role' => sys::code() . 'modules/roles/class/role.php',
        ];
        $classname = array_key_first($expected);
        $this->assertArrayHasKey($classname, $dataobjects);
        $this->assertEquals($expected[$classname], $dataobjects[$classname]);
        $this->assertGreaterThan(3, count($dataobjects));

        $modName = 'roles';
        $dataobjects = xarClassMap::getDataObjects($modName, null);
        $this->assertArrayHasKey($classname, $dataobjects);
        $this->assertEquals($expected[$classname], $dataobjects[$classname]);
        $this->assertCount(2, $dataobjects);

        $type = 'role';
        $dataobjects = xarClassMap::getDataObjects(null, $type);
        $this->assertArrayHasKey($classname, $dataobjects);
        $this->assertEquals($expected[$classname], $dataobjects[$classname]);
        $this->assertCount(2, $dataobjects);

        $dataobjects = xarClassMap::getDataObjects($modName, $type);
        $this->assertArrayHasKey($classname, $dataobjects);
        $this->assertEquals($expected[$classname], $dataobjects[$classname]);
        $this->assertCount(2, $dataobjects);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testFindDataObject(): void
    {
        $modName = 'roles';
        $type = 'role';
        // we have 2 classes here: Role and RoleList - pick one based on $suffix
        $suffix = 'RoleList';
        $result = xarClassMap::findDataObject($modName, $type, $suffix);

        $expected = [
            'classname' => 'RoleList',
            'filepath' => sys::code() . 'modules/roles/class/role.php',
            'classtype' => 'dataobjects',
            'module' => $modName,
            'filetype' => $type,
        ];
        $this->assertEquals($expected, $result);

        // we can instantiate dataobject instance without database (but not without objectdescriptor)
        //$defaults = get_class_vars($result['classname']);
        $defaults = [
            'name' => 'roles_users',
            'module' => 'roles',
            'itemtype' => 1,
        ];
        $descriptor = new VirtualObjectDescriptor($defaults);
        $instance = new $result['classname']($descriptor);
        $this->assertInstanceOf($expected['classname'], $instance);

        // we have 2 classes here: Role and RoleList - try without suffix to get duplicate exception
        $this->expectException(DuplicateException::class);
        $expected = 'Several "dataobjects" classes match module "roles" type "role"';
        $this->expectExceptionMessage($expected);

        $result = xarClassMap::findDataObject($modName, $type);
    }

    public function testGetProperties(): void
    {
        $properties = xarClassMap::getProperties();

        $expected = [
            'ArrayProperty' => sys::code() . 'modules/base/xarproperties/array.php',
        ];
        $classname = array_key_first($expected);
        $this->assertArrayHasKey($classname, $properties);
        $this->assertEquals($expected[$classname], $properties[$classname]);
        $this->assertGreaterThan(32, count($properties));

        $modName = 'base';
        $properties = xarClassMap::getProperties($modName, null);
        $this->assertArrayHasKey($classname, $properties);
        $this->assertEquals($expected[$classname], $properties[$classname]);
        $this->assertCount(32, $properties);

        $type = 'array';
        $properties = xarClassMap::getProperties(null, $type);
        $this->assertArrayHasKey($classname, $properties);
        $this->assertEquals($expected[$classname], $properties[$classname]);
        $this->assertCount(1, $properties);

        $properties = xarClassMap::getProperties($modName, $type);
        $this->assertArrayHasKey($classname, $properties);
        $this->assertEquals($expected[$classname], $properties[$classname]);
        $this->assertCount(1, $properties);
    }

    public function testFindProperty(): void
    {
        $modName = 'base';
        $type = 'array';
        $result = xarClassMap::findProperty($modName, $type);

        $expected = [
            'classname' => 'ArrayProperty',
            'filepath' => sys::code() . 'modules/base/xarproperties/array.php',
            'classtype' => 'properties',
            'module' => $modName,
            'filetype' => $type,
        ];
        $this->assertEquals($expected, $result);

        // we can instantiate property instance without database (but not without objectdescriptor)
        $defaults = get_class_vars($result['classname']);
        $descriptor = new ObjectDescriptor($defaults);
        $instance = new $result['classname']($descriptor);
        $this->assertInstanceOf($expected['classname'], $instance);

        $expected = $defaults;
        // these are set in ArrayProperty constructor
        $expected['tplmodule'] = $modName;
        $expected['template'] = $type;
        $expected['filepath'] = dirname(str_replace(sys::code(), '', $result['filepath']));
        // this is set by setValue() with empty default value
        $value = [];
        $expected['value'] = serialize($value);
        $this->assertEquals($expected, get_object_vars($instance));
    }

    public function testStandAloneProperties(): void
    {
        // we have 2 classes here: ListingProperty and ListingPropertyInstall but *Install is skipped
        $type = 'listing';
        $properties = xarClassMap::getProperties('', $type);
        $this->assertCount(1, $properties);
    }

    public function testFindStandAloneProperty(): void
    {
        $modName = '';
        $type = 'listing';
        $result = xarClassMap::findProperty($modName, $type);

        $expected = [
            'classname' => 'ListingProperty',
            'filepath' => sys::code() . 'properties/listing/main.php',
            'classtype' => 'properties',
            'module' => $modName,
            'filetype' => $type,
        ];
        $this->assertEquals($expected, $result);
    }

    public function testGetControllers(): void
    {
        $controllers = xarClassMap::getControllers();

        $expected = [
            'BaseShortController' => sys::code() . 'modules/base/controllers/short.php',
        ];
        $classname = array_key_first($expected);
        $this->assertArrayHasKey($classname, $controllers);
        $this->assertEquals($expected[$classname], $controllers[$classname]);
        $this->assertGreaterThan(4, count($controllers));

        $modName = 'base';
        $controllers = xarClassMap::getControllers($modName, null);
        $this->assertArrayHasKey($classname, $controllers);
        $this->assertEquals($expected[$classname], $controllers[$classname]);
        $this->assertCount(1, $controllers);

        $type = 'short';
        $controllers = xarClassMap::getControllers(null, $type);
        $this->assertArrayHasKey($classname, $controllers);
        $this->assertEquals($expected[$classname], $controllers[$classname]);
        $this->assertGreaterThan(2, count($controllers));

        $controllers = xarClassMap::getControllers($modName, $type);
        $this->assertArrayHasKey($classname, $controllers);
        $this->assertEquals($expected[$classname], $controllers[$classname]);
        $this->assertCount(1, $controllers);
    }

    public function testFindController(): void
    {
        $modName = 'base';
        $type = 'short';
        $result = xarClassMap::findController($modName, $type);

        $expected = [
            'classname' => 'BaseShortController',
            'filepath' => sys::code() . 'modules/base/controllers/short.php',
            'classtype' => 'controllers',
            'module' => $modName,
            'filetype' => $type,
        ];
        $this->assertEquals($expected, $result);

        // we can instantiate controller instance without database (but not without request)
        $expected = $result['classname'];
        $page = 'docs';
        $url = 'index.php?module=' . $modName . '&type=user&func=main&page=' . $page;
        $request = new xarRequest($url);
        $instance = new $result['classname']($request);
        $this->assertInstanceOf($expected, $instance);

        $expected = "/{$modName}/{$page}";
        $this->assertEquals($expected, $instance->encode($request));
    }

    public function testGetMiddleware(): void
    {
        $this->markTestSkipped('Middleware classes moved to core');
        $middleware = xarClassMap::getMiddleware();

        // we have 2 classes here: DataObjectMiddleware and DataObjectApiMiddleware
        $expected = [
            'Xaraya\Bridge\Middleware\DataObjectMiddleware' => sys::code() . 'modules/dynamicdata/controllers/middleware.php',
        ];
        $classname = array_key_first($expected);
        $this->assertArrayHasKey($classname, $middleware);
        $this->assertEquals($expected[$classname], $middleware[$classname]);
        $this->assertCount(6, $middleware);

        $modName = 'dynamicdata';
        $middleware = xarClassMap::getMiddleware($modName, null);
        $this->assertArrayHasKey($classname, $middleware);
        $this->assertEquals($expected[$classname], $middleware[$classname]);
        $this->assertCount(3, $middleware);

        $type = 'middleware';
        $middleware = xarClassMap::getMiddleware(null, $type);
        $this->assertArrayHasKey($classname, $middleware);
        $this->assertEquals($expected[$classname], $middleware[$classname]);
        $this->assertCount(4, $middleware);

        $middleware = xarClassMap::getMiddleware($modName, $type);
        $this->assertArrayHasKey($classname, $middleware);
        $this->assertEquals($expected[$classname], $middleware[$classname]);
        $this->assertCount(2, $middleware);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testFindMiddleware(): void
    {
        $this->markTestSkipped('Middleware classes moved to core');
        $modName = 'dynamicdata';
        $type = 'middleware';
        // we have 2 classes here: DataObjectMiddleware and DataObjectApiMiddleware - pick one based on $suffix
        $suffix = 'DataObjectApiMiddleware';
        $result = xarClassMap::findMiddleware($modName, $type, $suffix);

        $expected = [
            'classname' => 'Xaraya\Bridge\Middleware\DataObjectApiMiddleware',
            'filepath' => sys::code() . 'modules/dynamicdata/controllers/middleware.php',
            'classtype' => 'middleware',
            'module' => $modName,
            'filetype' => $type,
        ];
        $this->assertEquals($expected, $result);

        // we can instantiate middleware instance without database (but not without response factory)
        $responseFactory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $instance = new $result['classname']($responseFactory);
        $this->assertInstanceOf($expected['classname'], $instance);

        // we need database connection to run here
        $result = $instance->run(['object' => 'sample']);

        $expected = \Nyholm\Psr7\Response::class;
        $this->assertInstanceOf($expected, $result);
        $expected = 422;
        $this->assertEquals($expected, $result->getStatusCode());
        $expected = 'ResponseUtil Exception';
        $this->assertEquals($expected, $result->getReasonPhrase());
        $expected = 'Exception: No connection available';
        $this->assertStringContainsString($expected, (string) $result->getBody());

        // Note: error & exception handlers are set when importing xaraya.exceptions
        //restore_error_handler();
        //restore_exception_handler();
    }

    public function testGetHandlers(): void
    {
        $handlers = xarClassMap::getHandlers();

        // we only have 1 handler class per module for the moment
        $expected = [
            'Xaraya\\Modules\\DynamicData\\DynamicDataHandler' => sys::code() . 'modules/dynamicdata/controllers/handler.php',
        ];
        $classname = array_key_first($expected);
        $this->assertArrayHasKey($classname, $handlers);
        $this->assertEquals($expected[$classname], $handlers[$classname]);
        $this->assertGreaterThan(0, count($handlers));

        $modName = 'dynamicdata';
        $handlers = xarClassMap::getHandlers($modName, null);
        $this->assertArrayHasKey($classname, $handlers);
        $this->assertEquals($expected[$classname], $handlers[$classname]);
        $this->assertCount(1, $handlers);

        $type = 'handler';
        $handlers = xarClassMap::getHandlers(null, $type);
        $this->assertArrayHasKey($classname, $handlers);
        $this->assertEquals($expected[$classname], $handlers[$classname]);
        $this->assertGreaterThan(0, count($handlers));

        $handlers = xarClassMap::getHandlers($modName, $type);
        $this->assertArrayHasKey($classname, $handlers);
        $this->assertEquals($expected[$classname], $handlers[$classname]);
        $this->assertCount(1, $handlers);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testFindHandler(): void
    {
        $modName = 'dynamicdata';
        // we only have 1 handler class per module for the moment
        $type = null;
        $result = xarClassMap::findHandler($modName, $type);

        $expected = [
            'classname' => 'Xaraya\Modules\DynamicData\DynamicDataHandler',
            'filepath' => sys::code() . 'modules/dynamicdata/controllers/handler.php',
            'classtype' => 'handlers',
            'module' => $modName,
            'filetype' => $type,
        ];
        $this->assertEquals($expected, $result);

        // we can't get an instance without database here - UserGui relies on xarMod::load() in configure()
        $expected = 'No connection available';
        $this->expectExceptionMessage($expected);

        $instance = new \Xaraya\Modules\DynamicData\UserGui('dynamicdata');

        $expected = $result['classname'];
        $moduleHandler = new $result['classname']($instance);
        $this->assertInstanceOf($expected, $moduleHandler);

        $handler = ['dummy', 'main'];
        $vars = [];
        [$result, $context] = $moduleHandler->callHandler($handler, $vars);
    }

    public function testGetRoutes(): void
    {
        $routes = xarClassMap::getRoutes();

        // we only have 1 routes class per module for the moment
        $expected = [
            'Xaraya\\Modules\\DynamicData\\DynamicDataRoutes' => sys::code() . 'modules/dynamicdata/controllers/routes.php',
        ];
        $classname = array_key_first($expected);
        $this->assertArrayHasKey($classname, $routes);
        $this->assertEquals($expected[$classname], $routes[$classname]);
        $this->assertGreaterThan(1, count($routes));

        $modName = 'dynamicdata';
        $routes = xarClassMap::getRoutes($modName, null);
        $this->assertArrayHasKey($classname, $routes);
        $this->assertEquals($expected[$classname], $routes[$classname]);
        $this->assertCount(1, $routes);

        $type = 'routes';
        $routes = xarClassMap::getRoutes(null, $type);
        $this->assertArrayHasKey($classname, $routes);
        $this->assertEquals($expected[$classname], $routes[$classname]);
        $this->assertGreaterThan(1, count($routes));

        $routes = xarClassMap::getRoutes($modName, $type);
        $this->assertArrayHasKey($classname, $routes);
        $this->assertEquals($expected[$classname], $routes[$classname]);
        $this->assertCount(1, $routes);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testFindRoutes(): void
    {
        $modName = 'dynamicdata';
        // we only have 1 routes class per module for the moment
        $type = null;
        $result = xarClassMap::findRoutes($modName, $type);

        $expected = [
            'classname' => 'Xaraya\Modules\DynamicData\DynamicDataRoutes',
            'filepath' => sys::code() . 'modules/dynamicdata/controllers/routes.php',
            'classtype' => 'routes',
            'module' => $modName,
            'filetype' => '',
        ];
        $this->assertEquals($expected, $result);

        // check custom routes supported by DynamicDataRoutes
        $params = [
            'func' => 'view',
            'name' => 'sample',
        ];
        $expected = 'view-name';
        $route = $result['classname']::findCustomRouteName($params);
        $this->assertEquals($expected, $route);

        // we can't get an instance without database here - UserGui relies on xarMod::load() in configure()
        //$expected = 'No connection available';
        //$this->expectExceptionMessage($expected);
        xarDatabase::init();

        $route = 'dynamicdata-view-name';
        $context = new \Xaraya\Context\Context(['source' => __METHOD__]);
        $expected = \Xaraya\Modules\DynamicData\UserGui::class;
        $handler = $result['classname']::getHandler($route, $context);
        $this->assertInstanceOf($expected, $handler->getInstance());
    }

    public function testGetTables(): void
    {
        $tables = xarClassMap::getTables();

        // we only have 1 tables class per module for the moment
        $expected = [
            'Xaraya\\Modules\\DynamicData\\Tables' => sys::code() . 'modules/dynamicdata/tables.php',
        ];
        $classname = array_key_first($expected);
        $this->assertArrayHasKey($classname, $tables);
        $this->assertEquals($expected[$classname], $tables[$classname]);
        $this->assertGreaterThan(1, count($tables));

        $modName = 'dynamicdata';
        $tables = xarClassMap::getTables($modName);
        $this->assertArrayHasKey($classname, $tables);
        $this->assertEquals($expected[$classname], $tables[$classname]);
        $this->assertCount(1, $tables);

        // base module has no tables.php file
        $modName = 'base';
        $tables = xarClassMap::getTables($modName);
        $this->assertCount(0, $tables);
    }

    public function testFindTables(): void
    {
        $modName = 'dynamicdata';
        $result = xarClassMap::findTables($modName);

        $expected = [
            'classname' => 'Xaraya\Modules\DynamicData\Tables',
            'filepath' => sys::code() . 'modules/dynamicdata/tables.php',
            'classtype' => 'tables',
            'module' => $modName,
            'filetype' => 'tables',
        ];
        $this->assertEquals($expected, $result);

        $prefix = 'test';
        $tablesfunc = new $result['classname']();
        $tables = $tablesfunc($prefix);

        $expected = [
            'dynamic_objects' => 'test_dynamic_objects',
            'dynamic_properties' => 'test_dynamic_properties',
            'dynamic_data' => 'test_dynamic_data',
            'dynamic_relations' => 'test_dynamic_relations',
            'dynamic_properties_def' => 'test_dynamic_properties_def',
            'dynamic_configurations' => 'test_dynamic_configurations',
        ];
        $this->assertEquals($expected, $tables);

        // base module has no tables.php file
        $modName = 'base';
        $result = xarClassMap::findTables($modName);

        $expected = null;
        $this->assertEquals($expected, $result);
    }

    public function testGetVersions(): void
    {
        $versions = xarClassMap::getVersions();

        // we only have 1 version class per module for the moment
        $expected = [
            'Xaraya\\Modules\\DynamicData\\Version' => sys::code() . 'modules/dynamicdata/version.php',
        ];
        $classname = array_key_first($expected);
        $this->assertArrayHasKey($classname, $versions);
        $this->assertEquals($expected[$classname], $versions[$classname]);
        $this->assertGreaterThan(1, count($versions));

        $modName = 'dynamicdata';
        $versions = xarClassMap::getVersions($modName);
        $this->assertArrayHasKey($classname, $versions);
        $this->assertEquals($expected[$classname], $versions[$classname]);
        $this->assertCount(1, $versions);

        // invalid module has no version.php file
        $modName = 'invalid';
        $tables = xarClassMap::getTables($modName);
        $this->assertCount(0, $tables);
    }

    public function testFindVersion(): void
    {
        $modName = 'dynamicdata';
        $result = xarClassMap::findVersion($modName);

        $expected = [
            'classname' => 'Xaraya\Modules\DynamicData\Version',
            'filepath' => sys::code() . 'modules/dynamicdata/version.php',
            'classtype' => 'versions',
            'module' => $modName,
            'filetype' => 'version',
        ];
        $this->assertEquals($expected, $result);

        $versionfunc = new $result['classname']();
        $info = $versionfunc();

        $expected = [
            'name' => 'Dynamic Data',
        ];
        $key = array_key_first($expected);
        $this->assertArrayHasKey($key, $info);
        $this->assertEquals($expected[$key], $info[$key]);

        // invalid module has no version.php file
        $modName = 'invalid';
        $result = xarClassMap::findTables($modName);

        $expected = null;
        $this->assertEquals($expected, $result);
    }

    public function testGetModuleClasses(): void
    {
        $modules = xarClassMap::getModuleClasses();

        $expected = [
            'authsystem' => [
                'classname' => 'Xaraya\Modules\Authsystem\Module',
                'filepath' => sys::code() . 'modules/authsystem/module.php',
                'module' => 'authsystem',
            ],
        ];
        $modName = array_key_first($expected);
        $this->assertArrayHasKey($modName, $modules);
        $this->assertEquals($expected[$modName], $modules[$modName]);
    }

    public function testFindModuleClass(): void
    {
        $modName = 'authsystem';
        $result = xarClassMap::findModuleClass($modName);

        $expected = [
            'classname' => 'Xaraya\Modules\Authsystem\Module',
            'filepath' => sys::code() . 'modules/authsystem/module.php',
            'module' => $modName,
        ];
        $this->assertEquals($expected, $result);

        // we can instantiate module instance without database
        $expected = $result['classname'];
        $instance = new $result['classname']($modName);
        $this->assertInstanceOf($expected, $instance);
    }

    public function testFindModuleClassInvalid(): void
    {
        $modName = 'invalid';
        $result = xarClassMap::findModuleClass($modName);

        $expected = null;
        $this->assertEquals($expected, $result);
    }

    public function testGetModuleClassTypes(): void
    {
        $modName = 'authsystem';
        $classTypes = xarClassMap::getModuleClassTypes($modName);

        $expected = [
            'restapi' => [
                'classname' => 'Xaraya\Modules\Authsystem\RestApi',
                'filepath' => sys::code() . 'modules/authsystem/restapi.php',
                'module' => $modName,
                'classtype' => 'RestApi',
            ],
        ];
        $modType = array_key_first($expected);
        $this->assertArrayHasKey($modType, $classTypes);
        $this->assertEquals($expected[$modType], $classTypes[$modType]);
    }

    public function testFindModuleClassType(): void
    {
        $modName = 'authsystem';
        $modType = 'restapi';
        $result = xarClassMap::findModuleClassType($modName, $modType);

        $expected = [
            'classname' => 'Xaraya\Modules\Authsystem\RestApi',
            'filepath' => sys::code() . 'modules/authsystem/restapi.php',
            'module' => $modName,
            'classtype' => 'RestApi',
        ];
        $this->assertEquals($expected, $result);

        // we can instantiate module class instance without database (and without parent)
        $expected = $result['classname'];
        $instance = new $result['classname']($modName);
        $this->assertInstanceOf($expected, $instance);

        // we can call methods without database (and without parent) if they don't need it
        $result = $instance->getlist();

        $expected = [
            'honeypot' => [
                'path' => 'login',
                'method' => 'post',
                'description' => 'Call REST API honeypot() in module authsystem defined in code/modules/authsystem/xarrestapi/honeypot.php',
                'requestBody' => [
                    'application/json' => [
                        'username',
                        'password',
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    public function testGetModuleClassMethods(): void
    {
        $modName = 'authsystem';
        $modType = 'restapi';
        $methods = xarClassMap::getModuleClassMethods($modName, $modType);

        $expected = [
            'getlist' => [
                'classname' => 'Xaraya\Modules\Authsystem\RestApi\GetlistMethod',
                'filepath' => sys::code() . 'modules/authsystem/restapi/getlist.php',
                'method' => 'getlist',
            ],
        ];
        $funcName = array_key_first($expected);
        $this->assertArrayHasKey($funcName, $methods);
        $this->assertEquals($expected[$funcName], $methods[$funcName]);
    }

    public function testFindModuleClassMethod(): void
    {
        $modName = 'authsystem';
        $modType = 'restapi';
        $funcName = 'getlist';
        $result = xarClassMap::findModuleClassMethod($modName, $modType, $funcName);

        $expected = [
            'classname' => 'Xaraya\Modules\Authsystem\RestApi\GetlistMethod',
            'filepath' => sys::code() . 'modules/authsystem/restapi/getlist.php',
            'method' => 'getlist',
        ];
        $this->assertEquals($expected, $result);

        // we can't instantiate module class method without parent here
        $expected = 'Xaraya\Modules\MethodClass::setParent(): Argument #1 ($parent) must be of type Xaraya\Modules\ModuleServicesInterface, null given';
        $this->expectExceptionMessage($expected);

        $instance = new $result['classname']($modName);
        $this->assertInstanceOf($expected, $instance);
    }

    public function testWalkModuleMethods(): void
    {
        $modules = xarClassMap::getModuleClasses();
        foreach ($modules as $modName => $modInfo) {
            //echo $modName . ': ' . json_encode($modInfo) . "\n";
            $classTypes = xarClassMap::getModuleClassTypes($modName);
            //foreach ($classTypes as $classType => $classInfo) {
            //    echo "\t" . $classType . ': ' . json_encode($classInfo) . "\n";
            //}
            $classType = 'usergui';
            if (array_key_exists($classType, $classTypes)) {
                $methods = xarClassMap::getModuleClassMethods($modName, $classType);
                echo $modName . ' ' . $classType . ";\n";
                foreach ($methods as $methodName => $methodInfo) {
                    echo "\t" . $methodName . ': ' . $methodInfo['classname'] . "\n";
                }
            }
            echo "\n";
        }
        $this->assertGreaterThan(10, count($modules));
    }
}
