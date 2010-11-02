<?php
/**
 * Utility function to count the number of items held by this module
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles module
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
    return count(xarMod::apiFunc('roles','user','getallgroups'));
}

?>
