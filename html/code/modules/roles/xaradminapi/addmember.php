<?php
/**
 * Add a user to a group
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */
/**
 * insertuser - add a user to a group
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['id'] user id<br/>
 *        integer  $args['gid'] group id
 * @return boolean true on succes, false on failure
 */
function roles_adminapi_addmember(Array $args=array())
{
    return xarMod::apiFunc('roles','user','addmember',$args);
}

?>
