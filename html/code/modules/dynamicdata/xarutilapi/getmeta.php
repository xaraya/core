<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

sys::import('modules.dynamicdata.class.utilapi');
use Xaraya\DataObject\UtilApi;

/**
 * (try to) get the "meta" properties of tables via db abstraction layer
 *
 * @param array<string, mixed> $args
 * with
 *     $args['table']  optional table you're looking for
 *     $args['db']  optional database you're looking in (mysql only)
 *     $args['dbConnIndex'] connection index of the database if different from Xaraya DB (optional)
 *     $args['dbConnArgs'] connection params of the database if different from Xaraya DB (optional)
 * @return array<string, mixed>|void of field definitions, or null on failure
 * @todo split off the common parts which are also in getstatic.php
 */
function dynamicdata_utilapi_getmeta(array $args = [])
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

    return UtilApi::getMeta($table, $db, $dbConnIndex, $dbConnArgs);
}
