<?php
/**
 * Set a roles DUV
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * setduv - set the value of a DUV
 * @param $args['name'] name of the duv
 * @param $args['value'] value of the duv
 * @return boolean
 */
function roles_adminapi_setduv($args)
{
    extract($args);
    if (!isset($name))  throw new EmptyParameterException('name');
    if (!isset($value)) throw new EmptyParameterException('value'); 
    
    $uid = isset($uid) ? $uid : xarSessionGetVar('uid');

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $q = new xarQuery('SELECT',$xartables['roles'],'duvs');
    $q->eq('xar_uid', $uid);
    if (!$q->run($query)) return;
    $result = $q->row();
    $duvs = unserialize($result['duvs']);

    $done = false;
    for($i=0;$i<$limit;$i++) {
        if (($duvs[$i]['name'] == $name) && $duvs[$i]['state']) {
            $duvs[$i]['value'] = $value;
            $done = true;
            break;
        }
    }
    if (!$done) $duvs[] = array('name' => $name, 'value' => $value, 'state' => 1);

    $q = new xarQuery('UPDATE',$xartables['roles']);
    $q->addfield('duvs', serialize($duvs));
    $q->eq('xar_uid', $uid);
    if (!$q->run($query)) return;
    return true;
}

?>