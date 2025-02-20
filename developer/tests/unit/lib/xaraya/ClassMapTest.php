<?php

use PHPUnit\Framework\TestCase;

final class ClassMapTest extends TestCase
{
    public function testGetClassMap(): void
    {
        $classmap = xarClassMap::getClassMap();

        $expected = 1;
        $this->assertGreaterThan($expected, count($classmap));
        $expected = [
            'classname' => 'Xaraya\Authentication\AuthToken',
            'filepath' => '/html/code/modules/authsystem/class/authtoken.php',
        ];
        $this->assertArrayHasKey($expected['classname'], $classmap);
        $this->assertStringEndsWith($expected['filepath'], $classmap[$expected['classname']]);
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

    public function testGetModuleClassFiles(): void
    {
        $modName = 'base';
        $classfiles = xarClassMap::getModuleClassFiles($modName, '');

        $expected = [
            'BaseServerRequestSubject' => sys::code() . 'modules/base/class/eventsubjects/serverrequest.php',
        ];
        $classname = array_key_first($expected);
        $this->assertArrayHasKey($classname, $classfiles);
        $this->assertEquals($expected[$classname], $classfiles[$classname]);
        $this->assertCount(8, $classfiles);

        $type = 'eventsubjects';
        $classfiles = xarClassMap::getModuleClassFiles($modName, $type);
        $this->assertArrayHasKey($classname, $classfiles);
        $this->assertEquals($expected[$classname], $classfiles[$classname]);
        $this->assertCount(3, $classfiles);

        $event = 'ServerRequest';
        $filename = strtolower($event) . '.php';
        $classfiles = xarClassMap::getModuleClassFiles($modName, $type, $filename);
        $this->assertArrayHasKey($classname, $classfiles);
        $this->assertEquals($expected[$classname], $classfiles[$classname]);
        $this->assertCount(1, $classfiles);
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
        $subjects = xarClassMap::getEventSubjects($modName, '');
        $this->assertArrayHasKey($classname, $subjects);
        $this->assertEquals($expected[$classname], $subjects[$classname]);
        $this->assertCount(3, $subjects);

        $event = 'ServerRequest';
        $subjects = xarClassMap::getEventSubjects('', $event);
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
        $subjects = xarClassMap::getHookSubjects($modName, '');
        $this->assertArrayHasKey($classname, $subjects);
        $this->assertEquals($expected[$classname], $subjects[$classname]);
        $this->assertCount(22, $subjects);

        $event = 'ItemCreate';
        $subjects = xarClassMap::getHookSubjects('', $event);
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
        $observers = xarClassMap::getEventObservers($modName, '');
        $this->assertArrayHasKey($classname, $observers);
        $this->assertEquals($expected[$classname], $observers[$classname]);
        $this->assertCount(4, $observers);

        $event = 'ModActivate';
        $observers = xarClassMap::getEventObservers('', $event);
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
        $observers = xarClassMap::getHookObservers($modName, '');
        $this->assertArrayHasKey($classname, $observers);
        $this->assertEquals($expected[$classname], $observers[$classname]);
        $this->assertCount(10, $observers);

        $event = 'ItemCreate';
        $observers = xarClassMap::getHookObservers('', $event);
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

    public function testFindEventClassFile(): void
    {
        $type = 'eventsubjects';
        $modName = 'base';
        $event = 'ServerRequest';
        $result = xarClassMap::findEventClassFile($type, $modName, $event);

        $expected = [
            'classname' => 'BaseServerRequestSubject',
            'filepath' => sys::code() . 'modules/base/class/eventsubjects/serverrequest.php',
            'type' => $type,
            'module' => $modName,
            'event' => $event,
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
            'type' => 'hookobservers',
            'module' => $modName,
            'event' => $event,
        ];
        $this->assertEquals($expected, $result);

        // we can instantiate hookobserver instance without database (but not run notify)
        $instance = new $result['classname']();
        $this->assertInstanceOf($expected['classname'], $instance);

        $expected = $modName;
        $this->assertEquals($expected, $instance->getModName());
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
        $this->assertGreaterThan(33, count($properties));

        $modName = 'base';
        $properties = xarClassMap::getProperties($modName, '');
        $this->assertArrayHasKey($classname, $properties);
        $this->assertEquals($expected[$classname], $properties[$classname]);
        $this->assertCount(33, $properties);

        $type = 'array';
        $properties = xarClassMap::getProperties('', $type);
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
            'module' => $modName,
            'type' => $type,
        ];
        $this->assertEquals($expected, $result);

        // we can instantiate property instance without database (but not without objectdescriptor)
        $defaults = get_class_vars($result['classname']);
        $descriptor = new ObjectDescriptor($defaults);
        $instance = new $result['classname']($descriptor);

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
        // we have 2 classes here: ListingProperty and ListingPropertyInstall
        $type = 'listing';
        $properties = xarClassMap::getProperties('', $type);
        $this->assertCount(2, $properties);
    }

    public function testFindStandAloneProperty(): void
    {
        $modName = '';
        $type = 'listing';
        $result = xarClassMap::findProperty($modName, $type);

        $expected = [
            'classname' => 'ListingProperty',
            'filepath' => sys::code() . 'properties/listing/main.php',
            'module' => $modName,
            'type' => $type,
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
        $this->assertGreaterThan(10, count($controllers));

        $modName = 'base';
        $controllers = xarClassMap::getControllers($modName, '');
        $this->assertArrayHasKey($classname, $controllers);
        $this->assertEquals($expected[$classname], $controllers[$classname]);
        $this->assertCount(1, $controllers);

        $type = 'short';
        $controllers = xarClassMap::getControllers('', $type);
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
            'module' => $modName,
            'type' => $type,
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
}
