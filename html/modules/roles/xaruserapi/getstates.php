<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
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
    return array(
        array('id' => ROLES_STATE_INACTIVE, 'name' => xarML('Inactive')),
        array('id' => ROLES_STATE_NOTVALIDATED, 'name'  => xarML('Not Validated')),
        array('id' => ROLES_STATE_ACTIVE, 'name'  => xarML('Active')),
        array('id' => ROLES_STATE_PENDING, 'name'  => xarML('Pending'))
        );
}
?>
