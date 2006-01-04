<?php
/**
 * Get a roles DUV
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * getduv - get the value of a DUV
 * @param $args['name'] name of the duv
 * @return boolean
 */
function roles_adminapi_getduv($args)
{
    extract($args);
    if (!isset($name)) throw new EmptyParameterException('name');

    $uid = isset($uid) ? $uid : xarSessionGetVar('uid');

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $q = new xarQuery('SELECT',$xartables['roles'],'duvs');
    $q->eq('xar_uid', $uid);
    if (!$q->run($query)) return;
    $result = $q->row();
    $duvs = unserialize($result['duvs']);
    foreach ($duvs as $duv) {
        if (($duv['name'] == $name) && $duv['state']) {
            return $duv['value'];
            break;
        }
    }
    return;
}

?>