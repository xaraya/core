<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Get misc. information for dropdown lists
 *
 * @param $args['type'] the type of information you're looking for
 * @return array of info
 */
function dynamicdata_utilapi_getinfo($args = array())
{
    if (empty($args)) {
        $args['type'] = 'datastores';
    }

    $options = array();

    switch ($args['type'])
    {
        case 'datastores':
            $dbconn = xarDB::getConn();
            $dbInfo = $dbconn->getDatabaseInfo();
            $tables = $dbInfo->getTables();
            foreach ($tables as $tblInfo) {
                $tablename = $tblInfo->getName();
                $options[] = array('id' => $tablename, 'name' => $tablename);
            }
            break;

        case 'objectlinktypes':
            sys::import('modules.dynamicdata.class.objects.links');
            foreach (DataObjectLinks::$linktypes as $linktype => $descr) {
                $options[] = array('id' => $linktype, 'name' => $descr);
            }
            break;

        case 'objectdirections':
            sys::import('modules.dynamicdata.class.objects.links');
            foreach (DataObjectLinks::$directions as $direction => $descr) {
                $options[] = array('id' => $direction, 'name' => $descr);
            }
            break;

        case 'tablelinktypes':
            sys::import('modules.dynamicdata.class.datastores.links');
            foreach (DataStoreLinks::$linktypes as $linktype => $descr) {
                $options[] = array('id' => $linktype, 'name' => $descr);
            }
            break;

        case 'tabledirections':
            sys::import('modules.dynamicdata.class.datastores.links');
            foreach (DataStoreLinks::$directions as $direction => $descr) {
                $options[] = array('id' => $direction, 'name' => $descr);
            }
            break;
    }

    return $options;
}
?>
