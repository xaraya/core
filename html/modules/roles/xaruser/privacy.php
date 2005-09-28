<?php
/**
 * File: $Id$
 *
 * Shows the privacy policy if set as a modvar
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * Shows the privacy policy if set as a modvar
 */
function roles_user_privacy()
{
    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;
    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Privacy Statement')));
    return array();
}
?>