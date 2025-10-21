<?php

/**
 * Make Database Service available via facade (WIP)
 *
 * Classes that don't use ServicesInterface like xarMod(), xarUser() etc.
 * can more easily replace (most common) static xarDB::* method calls if
 * they use \Xaraya\Facades\xarDB3; instead
 *
 * @package core\facades
 * @subpackage facades
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Facades;

use Xaraya\Services\DatabaseInterface;
use Xaraya\Services\ServiceFactory;
use sys;

sys::import('xaraya.services.database');
sys::import('xaraya.services.servicefactory');

/**
 * Make Database Service available via facade - xarDB3:: static methods
 * similar to traditional xarDB::* method calls
 * @deprecated 2.8.2 use xar::db()->* instead
 */
class xarDB3
{
    /** @var ?DatabaseInterface */
    protected static $xarDB = null;         // Access database service with instance methods

    public static function getInstance(): DatabaseInterface
    {
        self::$xarDB ??= ServiceFactory::getDatabaseService(__METHOD__);
        return self::$xarDB;
    }

    /**
     * Summary of getConn
     */
    public static function &getConn(int|string $index = 0): object
    {
        // @todo support/combine external database as well
        return self::getInstance()->getConn($index);
    }

    /**
     * Summary of getFetchAssoc
     */
    public static function getFetchAssoc(): int
    {
        return self::getInstance()->getFetchAssoc();
    }

    /**
     * Summary of getFetchNum
     */
    public static function getFetchNum(): int
    {
        return self::getInstance()->getFetchNum();
    }

    /**
     * Summary of getName
     */
    public static function getName(): string
    {
        return self::getInstance()->getName();
    }

    /**
     * Summary of getPrefix
     */
    public static function getPrefix(): string
    {
        return self::getInstance()->getPrefix();
    }

    /**
     * Summary of getType
     */
    public static function getType(): string
    {
        return self::getInstance()->getType();
    }

    /**
     * Summary of getTables
     * @return array<string, string>
     */
    public static function &getTables(): array
    {
        return self::getInstance()->getTables();
    }

    /**
     * Summary of importTables
     * @param array<string, string> $tables
     */
    public static function importTables(array $tables = []): void
    {
        self::getInstance()->importTables($tables);
    }

    /**
     * Do we already have this database connection?
     */
    public static function hasConn(int|string $index = 0): bool
    {
        // @todo support/combine external database as well
        return self::getInstance()->hasConn($index);
    }

    /**
     * Initialise a new db connection
     * @param array<string, mixed>|null $args
     */
    public static function newConn(?array $args = null): object
    {
        // @todo support/combine external database as well
        return self::getInstance()->newConn($args);
    }

    /**
     * Get latest connection index
     */
    public static function getConnIndex(): int|string
    {
        // @todo support/combine external database as well
        return self::getInstance()->getConnIndex();
    }

    /**
     * Summary of getConnection
     * @param array<string, mixed> $dsn
     */
    public static function getConnection(array $dsn, mixed $flags): object
    {
        return self::getInstance()->getConnection($dsn, $flags);
    }

    /**
     * Summary of getTypeMap
     * @return array<mixed>
     */
    public static function getTypeMap(): array
    {
        return self::getInstance()->getTypeMap();
    }

    public static function withPDO(): bool
    {
        return self::getInstance()->withPDO();
    }
}
