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

use Xaraya\Database\ExternalDatabase;
use xarDatabase;
use xarDB;

/**
 * For documentation purposes only - available via DatabaseTrait
 * @todo align with xarDB, xarDB_Interface and ExternalDatabase
 * (not underlying xarDB_Creole, xarDB_PDO and external drivers)
 */
interface DatabaseInterface extends ServiceInterface
{
    public const SLICE = 'database';

    /** @param array<string, mixed> $dbConnArgs */
    public function checkDbConnection(mixed $dbConnIndex = 0, array $dbConnArgs = []): mixed;

    public function isIndexExternal(mixed $index): bool;

    public function setMiddleware(string $middlewareName): void;

    public function &getConn(int|string $index = 0): object;

    public function getFetchAssoc(): int;

    public function getFetchNum(): int;

    public function getName(): string;

    public function getPrefix(): string;

    public function setPrefix(string $prefix): void;

    public function getHost(): string;

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

    public function removeConn($index = null): bool;

    /** @return array<mixed> */
    public function getTypeMap(): array;

    /** @return array<mixed> */
    public function getDrivers(): array;

    public function withPDO(): bool;
}

/**
 * Database available via methods
 */
trait DatabaseTrait
{
    use ServiceTrait;

    /**
     * Initialize service class - this can be called several times
     * Note: for installer phase5 we set $args['doConnect'] = false
     * @param array<string, mixed> $config
     * @uses \xarDatabase::init()
     * @see \Xaraya\Modules\Installer\AdminGui\Phase5Method::__invoke()
     */
    public function init(array $config = []): bool
    {
        // @todo set sysConfig() defines here instead of auto-loaded file?
        return xarDatabase::init($config, $this->getParent());
    }

    protected function connect(array $config = [])
    {
        // ...
    }

    /**
     * Get configuration
     * @return array<string, mixed>
     * @uses \xarDatabase::getConfig()
     */
    public function getConfig(): array
    {
        return xarDatabase::getConfig($this->getParent());
    }

    /**
     * @param array<string, mixed> $dbConnArgs
     */
    public function checkDbConnection(mixed $dbConnIndex = 0, array $dbConnArgs = []): mixed
    {
        return ExternalDatabase::checkDbConnection($dbConnIndex, $dbConnArgs, $this->getParent());
    }

    public function isIndexExternal(mixed $index): bool
    {
        return ExternalDatabase::isIndexExternal($index);
    }

    public function setMiddleware(string $middlewareName): void
    {
        xarDB::setMiddleware($middlewareName);
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
        return xarDB::getFetchAssoc();
    }

    /**
     * Summary of getFetchNum
     */
    public function getFetchNum(): int
    {
        return xarDB::getFetchNum();
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

    public function setPrefix(string $prefix): void
    {
        xarDB::setPrefix($prefix);
    }

    public function getHost(): string
    {
        return xarDB::getHost();
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
        return xarDB::newConn($args, $this->getParent());
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

    public function removeConn($index = null): bool
    {
        return xarDB::removeConn($index);
    }

    /**
     * Summary of getTypeMap
     * @return array<mixed>
     */
    public function getTypeMap(): array
    {
        return xarDB::getTypeMap();
    }

    /**
     * @return array<mixed>
     */
    public function getDrivers(): array
    {
        return xarDB::getDrivers();
    }

    public function withPDO(): bool
    {
        return xarDB::withPDO();
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
