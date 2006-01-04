<?php
/**
 * Activate a roles DUV
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * activateduv - set a DUV in the roles module to active state
 * @param $args['name'] name of the duv
 * @return boolean
 */
function roles_adminapi_activateduv($args)
{
    extract($args);
    if (!isset($name)) throw new EmptyParameterException('name');

    $duvs = xarModGetVar('roles','duvs');
    if (isset($duvs)) $duvs = unserialize($duvs);
    $duvs[$name] = array('state' => 1);
    xarModSetVar('roles','duvs',serialize($duvs));
    return true;
}

?>