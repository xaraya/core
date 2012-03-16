<?php
/**
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * Get States
 *
 * @author Marc Lutolf
 * @todo this either needs to move somewhere else, or become smarter (ie: use workflow)
 *       there's no current way this can be included in the property configuration
 *       since property configuration can't be MLed
 */
function roles_userapi_getstates()
{
    sys::import('modules.roles.class.roles');
    return array(
        array('id' => xarRoles::ROLES_STATE_INACTIVE, 'name' => xarML('Inactive')),
        array('id' => xarRoles::ROLES_STATE_NOTVALIDATED, 'name'  => xarML('Not Validated')),
        array('id' => xarRoles::ROLES_STATE_ACTIVE, 'name'  => xarML('Active')),
        array('id' => xarRoles::ROLES_STATE_PENDING, 'name'  => xarML('Pending'))
        );
}
?>
