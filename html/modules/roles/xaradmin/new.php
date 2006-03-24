<?php
/**
 * File: $Id$
 *
 * Create a new role
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
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
    return xarModFunc('roles', 'admin', 'newrole');
}
?>