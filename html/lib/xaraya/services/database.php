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
 */
interface DatabaseInterface
{
    /** Adapted from ServiceInterface to allow any parent here */

    public function __construct(mixed $parent = null);

    public function getParent(): mixed;

    public static function create(mixed $parent = null): DatabaseInterface;

    /** Service-specific methods */

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
}

/**
 * Database available via methods
 */
trait DatabaseTrait
{
    /** Adapted from ServiceTrait to allow any parent here */

    protected static ?DatabaseInterface $xarDB = null;
    public mixed $parent;

    /**
     * Create service class for parent
     */
    public function __construct(mixed $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Get parent of service class
     */
    public function getParent(): mixed
    {
        return $this->parent;
    }

    /**
     * Summary of create
     */
    public static function create(mixed $parent = null): DatabaseInterface
    {
        // create singleton instance for any parent here
        self::$xarDB ??= new self($parent);
        return self::$xarDB;
    }

    /** Service-specific methods */

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
