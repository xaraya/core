<?php

/**
 * Make Database Service available via trait (WIP)
 *
 * Classes that don't use ServicesInterface like Query(), cache storage etc.
 * can more easily replace (most common) static xarDB::* method calls if
 * they use \Xaraya\Services\HasDatabaseTrait; instead
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

use sys;

sys::import('xaraya.services.database');
sys::import('xaraya.services.servicefactory');

/**
 * Make Database Service available via trait
 */
trait HasDatabaseTrait
{
    /** @var ?DatabaseInterface */
    protected $xarDB = null;         // Access database service with instance methods

    /**
     * Access database service
     * @todo see if the caller can pass this along someday (dependency injection)
     */
    protected function db(): DatabaseInterface
    {
        $this->xarDB ??= ServiceFactory::getDatabaseService($this);
        return $this->xarDB;
    }
}
