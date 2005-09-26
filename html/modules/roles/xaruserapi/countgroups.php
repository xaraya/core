<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * utility function to count the number of items held by this module
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns integer
 * @return number of items held by this module
 * @raise DATABASE_ERROR
 */
function roles_userapi_countgroups()
{
    return count(xarModAPIFunc('roles','user','getallgroups'));
}

?>