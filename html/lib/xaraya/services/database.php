<?php

/**
 * Database available via methods (WIP)
 *
 * For classes with ServicesInterface, but also for Query(), cache storage etc.
 * to replace (most common) static xarDB::* method calls
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use xarDB;

//use sys;

// this does not re-use ServiceInterface
//sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via DatabaseTrait
 * @todo align with xarDB, xarDB_Interface and ExternalDatabase
 * (not underlying xarDB_Creole, xarDB_PDO and external drivers)
 */
interface DatabaseInterface extends ServiceInterface
{
    public static function create(mixed $parent = null): DatabaseInterface;

    public function &getConn(int|string $index = 0): object;

    public function getFetchAssoc(): int;

    public function getFetchNum(): int;

    public function getName(): string;

    public function getPrefix(): string;

    public function getType(): string;

    /** @return array<string, string> */
    public function &getTables(): array;

    /** @param array<string, string> $tables */
    public function importTables(array $tables = []): void;

    public function hasConn(int|string $index = 0): bool;

    /** @param array<string, mixed>|null $args */
    public function newConn(?array $args = null): object;

    public function getConnIndex(): int|string;

    /** @param array<string, mixed> $dsn */
    public function getConnection(array $dsn, mixed $flags): object;

    /** @return array<mixed> */
    public function getTypeMap(): array;
}

/**
 * Database available via methods
 */
trait DatabaseTrait
{
    use ServiceTrait;

    protected static ?DatabaseInterface $xarDB = null;

    /**
     * Summary of create
     */
    public static function create(mixed $parent = null): DatabaseInterface
    {
        // create singleton instance for any parent here
        self::$xarDB ??= new self($parent);
        return self::$xarDB;
    }

    /**
     * Summary of getConn
     */
    public function &getConn(int|string $index = 0): object
    {
        // @todo support/combine external database as well
        return xarDB::getConn($index);
    }

    /**
     * Summary of getFetchAssoc
     */
    public function getFetchAssoc(): int
    {
        return xarDB::FETCHMODE_ASSOC;
    }

    /**
     * Summary of getFetchNum
     */
    public function getFetchNum(): int
    {
        return xarDB::FETCHMODE_NUM;
    }

    /**
     * Summary of getName
     */
    public function getName(): string
    {
        return xarDB::getName();
    }

    /**
     * Summary of getPrefix
     */
    public function getPrefix(): string
    {
        return xarDB::getPrefix();
    }

    /**
     * Summary of getType
     */
    public function getType(): string
    {
        return xarDB::getType();
    }

    /**
     * Summary of getTables
     * @return array<string, string>
     */
    public function &getTables(): array
    {
        return xarDB::getTables();
    }

    /**
     * Summary of importTables
     * @param array<string, string> $tables
     */
    public function importTables(array $tables = []): void
    {
        xarDB::importTables($tables);
    }

    /**
     * Do we already have this database connection?
     */
    public function hasConn(int|string $index = 0): bool
    {
        // @todo support/combine external database as well
        return xarDB::hasConn($index);
    }

    /**
     * Initialise a new db connection
     * @param array<string, mixed>|null $args
     */
    public function newConn(?array $args = null): object
    {
        // @todo support/combine external database as well
        return xarDB::newConn($args);
    }

    /**
     * Get latest connection index
     */
    public function getConnIndex(): int|string
    {
        // @todo support/combine external database as well
        return xarDB::getConnIndex();
    }

    /**
     * Summary of getConnection
     * @param array<string, mixed> $dsn
     */
    public function getConnection(array $dsn, mixed $flags): object
    {
        return xarDB::getConnection($dsn, $flags);
    }

    /**
     * Summary of getTypeMap
     * @return array<mixed>
     */
    public function getTypeMap(): array
    {
        return xarDB::getTypeMap();
    }
}

/**
 * Access xarDB::* Database methods (getConn, getPrefix, ...)
 *
 * Available methods:
 * - getConn()
 * - getFetchAssoc()
 * - getFetchEnum()
 * - getPrefix()
 * - getType()
 * - getTables()
 * - importTables()
 * - ...
 *
 */
class DatabaseService implements DatabaseInterface
{
    use DatabaseTrait;
}
