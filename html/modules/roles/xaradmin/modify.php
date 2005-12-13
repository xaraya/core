<?php
/**
 * Modify a role
 *
 * @package modules
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage roles
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
    return xarModFunc('roles', 'admin', 'modifyrole');
}
?>