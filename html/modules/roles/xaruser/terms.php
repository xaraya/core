<?php
/**
 * File: $Id$
 *
 * Shows the user terms if set as a mod var
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * Shows the user terms if set as a modvar
 */
function roles_user_terms()
{
    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;
    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Terms of Usage')));
    return array();
}
?>