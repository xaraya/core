<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\UtilApi;

use Xaraya\DataObject\MethodClass;
use Xaraya\DataObject\UtilApi;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata utilapi getmeta function
 * @extends MethodClass<UtilApi>
 */
class GetmetaMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * (try to) get the "meta" properties of tables via db abstraction layer
     * @param array<string,mixed> $args
     * with
     *     $args['table']  optional table you're looking for
     *     $args['db']  optional database you're looking in (mysql only)
     *     $args['dbConnIndex'] connection index of the database if different from Xaraya DB (optional)
     *     $args['dbConnArgs'] connection params of the database if different from Xaraya DB (optional)
     * @return array<string,mixed>|void of field definitions, or null on failure
     * @todo split off the common parts which are also in getstatic.php
     * @see UtilApi::getmeta()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (empty($table)) {
            $table = '';
        }
        if (empty($db)) {
            $db = null;
        }
        if (empty($dbConnIndex)) {
            $dbConnIndex = 0;
        }
        if (empty($dbConnArgs)) {
            $dbConnArgs = [];
        }
        $utilapi = new \Xaraya\DataObject\UtilApi();

        return $utilapi->getMetaInfo($table, $db, $dbConnIndex, $dbConnArgs);
    }
}
