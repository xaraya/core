<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Database\ExternalDatabase;

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
        $search = [];
        $replace = [];
        if ($middleware == 'Creole') {
            $search[] = "\$systemConfiguration['DB.Middleware'] = 'PDO';";
            $replace[] = "\$systemConfiguration['DB.Middleware'] = 'Creole';";
            $search[] = "\$systemConfiguration['DB.Type'] = 'pdomysqli';";
            $replace[] = "\$systemConfiguration['DB.Type'] = 'mysqli';";
        } else {
            $search[] = "\$systemConfiguration['DB.Middleware'] = 'Creole';";
            $replace[] = "\$systemConfiguration['DB.Middleware'] = 'PDO';";
            $search[] = "\$systemConfiguration['DB.Type'] = 'mysqli';";
            $replace[] = "\$systemConfiguration['DB.Type'] = 'pdomysqli';";
        }
        $fileName = sys::varpath() . '/' . sys::CONFIG;
        $content = file_get_contents($fileName);
        $content = str_replace($search, $replace, $content);
        file_put_contents($fileName, $content);
    }

    public function testCreoleMiddleware(): void
    {
        $expected = 'Creole';
        self::useMiddleware($expected);

        // check we get the expected classes
        $middleware = xarSystemVars::get(sys::CONFIG, 'DB.Middleware');
        $this->assertEquals($expected, $middleware);
        xarDatabase::init();
        //$this->assertTrue(is_subclass_of('xarDB', 'xarDB_Creole'));
        $conn = xarDB::getConn();
        $this->assertTrue($conn instanceof \Connection);
        // @todo align FETCHMODE constants between Creole & PDO interfaces
        $expected = ResultSet::FETCHMODE_ASSOC;
        $this->assertEquals($expected, xarDB::FETCHMODE_ASSOC);
        $expected = ResultSet::FETCHMODE_NUM;
        $this->assertEquals($expected, xarDB::FETCHMODE_NUM);

        // check database connection works
        $expected = 'xar_eventsystem';
        $dbInfo = $conn->getDatabaseInfo();
        $table = $dbInfo->getTable($expected);
        $this->assertEquals($expected, $table->getName());

        // check getConn vs. hasConn due to auto-connect
        $expected = 2146023659;
        $this->assertEquals($expected, xarDB::getConnIndex());
        $this->assertFalse(xarDB::hasConn(1));
        $conn = xarDB::getConn(1);
        $expected = 2146023659;
        $this->assertEquals($expected, xarDB::getConnIndex());
        $this->assertTrue($conn instanceof \Connection);
        $this->assertTrue(xarDB::hasConn($expected));

        // check new connection to other database
        $dbConnArgs = [
            'databaseType' => 'pdosqlite',
            'databaseName' => sys::varpath() . '/sqlite/metadata.db',
        ];
        $conn = xarDB::newConn($dbConnArgs);
        $dbConnIndex = xarDB::getConnIndex();
        $expected = 4125574640;
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

    public function testPDOMiddleware(): void
    {
        $expected = 'PDO';
        self::useMiddleware($expected);

        // check we get the expected classes
        $middleware = xarSystemVars::get(sys::CONFIG, 'DB.Middleware');
        $this->assertEquals($expected, $middleware);
        xarDatabase::init();
        //$this->assertTrue(is_subclass_of('xarDB', 'xarDB_PDO'));
        $conn = xarDB::getConn();
        $this->assertTrue($conn instanceof \PDOConnection);
        // @todo align FETCHMODE constants between Creole & PDO interfaces
        $expected = PDO::FETCH_ASSOC;
        $this->assertEquals($expected, xarDB::FETCHMODE_ASSOC);
        $expected = PDO::FETCH_NUM;
        $this->assertEquals($expected, xarDB::FETCHMODE_NUM);

        // check database connection works
        $expected = 'xar_eventsystem';
        $dbInfo = $conn->getDatabaseInfo();
        $table = $dbInfo->getTable($expected);
        $this->assertEquals($expected, $table->getName());

        // check getConn vs. hasConn due to auto-connect
        $expected = 2989598190;
        $this->assertEquals($expected, xarDB::getConnIndex());
        $this->assertFalse(xarDB::hasConn(1));
        $conn = xarDB::getConn(1);
        $expected = 2989598190;
        $this->assertEquals($expected, xarDB::getConnIndex());
        $this->assertTrue($conn instanceof \PDOConnection);
        $this->assertTrue(xarDB::hasConn($expected));

        // check new connection to other database
        $dbConnArgs = [
            'databaseType' => 'pdosqlite',
            'databaseName' => sys::varpath() . '/sqlite/metadata.db',
        ];
        $conn = xarDB::newConn($dbConnArgs);
        $dbConnIndex = xarDB::getConnIndex();
        $expected = 2157930189;
        $this->assertEquals($expected, $dbConnIndex);
        $this->assertTrue($conn instanceof \PDOConnection);
        $this->assertTrue(xarDB::hasConn($dbConnIndex));

        // use connection to other database
        $conn = xarDB::getConn($dbConnIndex);
        $dbInfo = $conn->getDatabaseInfo();
        $tables = $dbInfo->getTables();
        $expected = 0;
        $this->assertCount($expected, $tables);
    }

    public function testExternalPDODriver(): void
    {
        $dbConnArgs = [
            'databaseType' => 'sqlite',
            'databaseName' => sys::varpath() . '/sqlite/metadata.db',
        ];
        $dbConnArgs['external'] = 'pdo';
        $conn = ExternalDatabase::newConn($dbConnArgs);
        $this->assertTrue($conn instanceof \PDO);

        // use native methods on connection and succeed
        $expected = 'sqlite';
        $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->assertEquals($expected, $driver);

        // try static method on external database and fail
        $this->expectException(BadMethodCallException::class);
        $type = ExternalDatabase::getType();
    }

    public function testExternalDBALDriver(): void
    {
        $dbConnArgs = [
            'databaseType' => 'sqlite3',
            'databaseName' => sys::varpath() . '/sqlite/metadata.db',
        ];
        $dbConnArgs['external'] = 'dbal';
        $conn = ExternalDatabase::newConn($dbConnArgs);
        $this->assertTrue($conn instanceof \Doctrine\DBAL\Connection);

        // use native methods on connection and succeed
        $expected = 'Doctrine\DBAL\Driver\SQLite3\Driver';
        $driver = $conn->getDriver();
        $this->assertEquals($expected, get_class($driver));

        // try static method on external database and fail
        $this->expectException(BadMethodCallException::class);
        $type = ExternalDatabase::getType();
    }
}
