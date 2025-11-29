<?php

/**
 * Make Core Services available via xar::service() etc. in static methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use xarConst;
use xarCore;

/**
 * Make Core Services available via xar::service() etc. in static methods (WIP)
 *
 * ```
 * use Xaraya\Services\xar;
 *
 * class SomethingInteresting
 * {
 *     public static function helloWorld():
 *     {
 *         $cache = xar::service('cache);
 *         $dbconn = xar::db()->getConn();
 *         // ...
 *     }
 * }
 * ```
 */
class xar extends xarConst
{
    use WithStaticServices;

    /**
     * Initialize core with xar::load(); static (global context)
     * @todo differentiate between site init and request init
     * @return StaticServicesClass
     */
    public static function load($whatToLoad = xarConst::SYSTEM_ALL, $context = null)
    {
        /**
         * Get context from globals if not specified (default)
         */
        if (is_null($context)) {
            $context = \Xaraya\Context\ContextFactory::fromGlobals(__METHOD__);
        }
        // Set context for core services here first + return static services class
        $xar = self::setServicesContext($context);

        // Initialize core with current services instance
        if (!$xar->load($whatToLoad)) {
            throw new \RuntimeException('Unable to load Xaraya core');
        }
        return $xar;
    }

    public static function isLoaded($checkLevel)
    {
        return xarCore::isLoaded($checkLevel);
    }

    /**
     * Get the public properties of an object (this must be done outside the class)
     * @param object $object
     * @return array<string, mixed>
     */
    public static function getPublicProperties($object)
    {
        return get_object_vars($object);
    }
}
