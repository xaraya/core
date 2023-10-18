<?php

use PHPUnit\Framework\TestCase;

/**
 * We need to run each test in a separate process here to switch databases
 * and disable preserving global state to avoid phpunit serialize issues
 * https://docs.phpunit.de/en/9.6/annotations.html#appendixes-annotations-preserveglobalstate
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class DatabaseTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        //xarCache::init();
    }

    public static function tearDownAfterClass(): void
    {
        self::useMiddleware('Creole');
    }

    protected static function useMiddleware($middleware = 'Creole'): void
    {
        if ($middleware == 'Creole') {
            $search = 'PDO';
        } else {
            $search = 'Creole';
        }
        $fileName = sys::varpath() . '/' . sys::CONFIG;
        $content = file_get_contents($fileName);
        $content = str_replace("\$systemConfiguration['DB.Middleware'] = '" . $search . "';", "\$systemConfiguration['DB.Middleware'] = '" . $middleware . "';", $content);
        file_put_contents($fileName, $content);
    }

    public function testCreoleDatabase(): void
    {
        $expected = 'Creole';
        self::useMiddleware($expected);

        // check we get the expected classes
        $middleware = xarSystemVars::get(sys::CONFIG, 'DB.Middleware');
        $this->assertEquals($expected, $middleware);
        xarDatabase::init();
        $this->assertTrue(is_subclass_of('xarDB', 'xarDB_Creole'));
        $conn = xarDB::getConn();
        $this->assertTrue($conn instanceof \Connection);
        // @todo align FETCHMODE constants between Creole & PDO interfaces
        $expected = 1;
        $this->assertEquals($expected, xarDB::FETCHMODE_ASSOC);
        $this->assertEquals($expected, ResultSet::FETCHMODE_ASSOC);
        $expected = 2;
        $this->assertEquals($expected, xarDB::FETCHMODE_NUM);
        $this->assertEquals($expected, ResultSet::FETCHMODE_NUM);

        // check database connection works
        $expected = 'xar_eventsystem';
        $dbInfo = $conn->getDatabaseInfo();
        $table = $dbInfo->getTable($expected);
        $this->assertEquals($expected, $table->getName());

        // check getConn vs. hasConn due to auto-connect
        $expected = 1;
        $this->assertEquals($expected, xarDB::$count);
        $this->assertFalse(xarDB::hasConn(1));
        $conn = xarDB::getConn(1);
        $expected = 2;
        $this->assertEquals($expected, xarDB::$count);
        $this->assertTrue($conn instanceof \Connection);
        $this->assertTrue(xarDB::hasConn(1));

        // check new connection to other database
        $dbConnArgs = [
            'databaseType' => 'sqlite3',
            'databaseName' => sys::varpath() . '/sqlite/metadata.db',
        ];
        $conn = xarDB::newConn($dbConnArgs);
        $dbConnIndex = xarDB::$count - 1;
        $expected = 2;
        $this->assertEquals($expected, $dbConnIndex);
        $this->assertTrue($conn instanceof \Connection);
        $this->assertTrue(xarDB::hasConn($dbConnIndex));

        // use connection to other database
        $conn = xarDB::getConn($dbConnIndex);
        $dbInfo = $conn->getDatabaseInfo();
        $tables = $dbInfo->getTables();
        $expected = 0;
        $this->assertCount($expected, $tables);
    }

    public function testPDODatabase(): void
    {
        $expected = 'PDO';
        self::useMiddleware($expected);

        // check we get the expected classes
        $middleware = xarSystemVars::get(sys::CONFIG, 'DB.Middleware');
        $this->assertEquals($expected, $middleware);
        xarDatabase::init();
        $this->assertTrue(is_subclass_of('xarDB', 'xarDB_PDO'));
        $conn = xarDB::getConn();
        $this->assertTrue($conn instanceof \xarPDO);
        // @todo align FETCHMODE constants between Creole & PDO interfaces
        $expected = 2;
        $this->assertEquals($expected, xarDB::FETCHMODE_ASSOC);
        $this->assertEquals($expected, ResultSet::FETCHMODE_ASSOC);
        $expected = 3;
        $this->assertEquals($expected, xarDB::FETCHMODE_NUM);
        $this->assertEquals($expected, ResultSet::FETCHMODE_NUM);

        // check database connection works
        $expected = 'xar_eventsystem';
        $dbInfo = $conn->getDatabaseInfo();
        $table = $dbInfo->getTable($expected);
        $this->assertEquals($expected, $table->getName());

        // check getConn vs. hasConn due to auto-connect
        $expected = 1;
        $this->assertEquals($expected, xarDB::$count);
        $this->assertFalse(xarDB::hasConn(1));
        $conn = xarDB::getConn(1);
        $expected = 2;
        $this->assertEquals($expected, xarDB::$count);
        $this->assertTrue($conn instanceof \xarPDO);
        $this->assertTrue(xarDB::hasConn(1));

        // check new connection to other database
        $dbConnArgs = [
            'databaseType' => 'sqlite3',
            'databaseName' => sys::varpath() . '/sqlite/metadata.db',
        ];
        $conn = xarDB::newConn($dbConnArgs);
        $dbConnIndex = xarDB::$count - 1;
        $expected = 2;
        $this->assertEquals($expected, $dbConnIndex);
        $this->assertTrue($conn instanceof \xarPDO);
        $this->assertTrue(xarDB::hasConn($dbConnIndex));

        // use connection to other database
        $conn = xarDB::getConn($dbConnIndex);
        $dbInfo = $conn->getDatabaseInfo();
        $tables = $dbInfo->getTables();
        $expected = 0;
        $this->assertCount($expected, $tables);
    }
}
