<?php
/**
 * File: $Id$
 *
 * Modify a role
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf
 */
/**
 * modify - generic wrapper to modify an item
 * Takes no parameters
 *
 * @author Marc Lutolf
 */
function roles_admin_modify()
{
    if (!xarVarFetch('itemid', 'int', $itemid, NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemtype', 'int', $itemtype, NULL, XARVAR_NOT_REQUIRED)) return;
    return xarModFunc('roles', 'admin', 'modifyrole',array('itemid' => $itemid));
}
?>