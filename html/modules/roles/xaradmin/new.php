<?php
/**
 * File: $Id$
 *
 * Create a new role
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf
 */
/**
 * new - generic wrapper to create a new item
 * Takes no parameters
 *
 * @author Marc Lutolf
 */
function roles_admin_new()
{
    if (!xarVarFetch('itemtype', 'int', $itemtype, USERTYPE, XARVAR_NOT_REQUIRED)) return;
    return xarModFunc('roles', 'admin', 'newrole',array('itemtype' => $itemtype));
}
?>