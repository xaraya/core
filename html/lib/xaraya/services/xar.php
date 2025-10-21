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
class xar
{
    use WithStaticServices;
}
