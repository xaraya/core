<?php
/**
 * Delete a role
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * delete - delete a role
 */
function roles_admin_delete()
{
	xarModFunc('roles','admin','deleterole');
}
?>
