<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\UtilApi;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\UtilApi;
use DataObjectLinks;
use DataStoreLinks;
use xarDB;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata utilapi getinfo function
 * @extends MethodClass<UtilApi>
 */
class GetinfoMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get misc. information for dropdown lists
     * @param array<string,mixed> $args
     * with
     *     $args['type'] the type of information you're looking for
     * @return array of info
     * @see UtilApi::getinfo()
     */
    public function __invoke(array $args = [])
    {
        if (empty($args)) {
            $args['type'] = 'datastores';
        }

        $options = [];

        switch ($args['type']) {
            case 'datastores':
                $dbconn = $this->db()->getConn();
                $dbInfo = $dbconn->getDatabaseInfo();
                $tables = $dbInfo->getTables();
                foreach ($tables as $tblInfo) {
                    $tablename = $tblInfo->getName();
                    $options[] = ['id' => $tablename, 'name' => $tablename];
                }
                break;

            case 'objectlinktypes':
                sys::import('modules.dynamicdata.class.objects.links');
                foreach (DataObjectLinks::$linktypes as $linktype => $descr) {
                    $options[] = ['id' => $linktype, 'name' => $descr];
                }
                break;

            case 'objectdirections':
                sys::import('modules.dynamicdata.class.objects.links');
                foreach (DataObjectLinks::$directions as $direction => $descr) {
                    $options[] = ['id' => $direction, 'name' => $descr];
                }
                break;

            case 'tablelinktypes':
                sys::import('modules.dynamicdata.class.datastores.links');
                foreach (DataStoreLinks::$linktypes as $linktype => $descr) {
                    $options[] = ['id' => $linktype, 'name' => $descr];
                }
                break;

            case 'tabledirections':
                sys::import('modules.dynamicdata.class.datastores.links');
                foreach (DataStoreLinks::$directions as $direction => $descr) {
                    $options[] = ['id' => $direction, 'name' => $descr];
                }
                break;
        }

        return $options;
    }
}
