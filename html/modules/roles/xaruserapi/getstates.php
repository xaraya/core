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
 *       there's no current way this can be included in the property validation
 *       since property validation can't be MLed
 */
function roles_userapi_getstates()
{
    return array(ROLES_STATE_INACTIVE => xarML('Inactive'),
                 ROLES_STATE_NOTVALIDATED => xarML('Not Validated'),
                 ROLES_STATE_ACTIVE => xarML('Active'),
                 ROLES_STATE_PENDING => xarML('Pending'));
}
?>
