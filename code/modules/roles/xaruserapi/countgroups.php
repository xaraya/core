<?php
/**
 * Utility function to count the number of items held by this module
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * utility function to count the number of items held by this module
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns integer
 * @return number of items held by this module
 * @throws DATABASE_ERROR
 */
function roles_userapi_countgroups()
{
    return count(xarModAPIFunc('roles','user','getallgroups'));
}

?>
