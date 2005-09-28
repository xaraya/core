<?php
/**
 * File: $Id$
 *
 * Utility function to count the number of items held by this module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * utility function to count the number of items held by this module
 *
 * @author the Example module development team
 * @returns integer
 * @return number of items held by this module
 * @raise DATABASE_ERROR
 */
function roles_userapi_countgroups()
{
    return count(xarModAPIFunc('roles','user','getallgroups'));
}

?>