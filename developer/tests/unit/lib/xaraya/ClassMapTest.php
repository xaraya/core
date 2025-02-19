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
        $paths = [
            sys::code() . 'modules/base/xarblocks/menu/menu_display.php',
            sys::code() . 'modules/base/xarblocks/menu/display.php',
            sys::code() . 'modules/base/xarblocks/menu/menu.php',
            sys::code() . 'modules/base/xarblocks/menu_display.php',
            sys::code() . 'modules/base/xarblocks/menu.php',
        ];
        $result = xarClassMap::findBlock($paths);

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
        $result = xarClassMap::findBlock($paths);

        $expected = [
            'filepath' => '',
            'found' => [],
        ];
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
            'module' => 'authsystem',
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
