<?php
/**
 * Utility function to retrieve the list of item types of this module (if any)
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
 * Wrapper for Dynamic_Object_Master::getAncestors
 *
 * @see  Dynamic_Object_Master::getAncestors
 * @todo remove this wrapper
 */
function &dynamicdata_userapi_getancestors($args)
{
    return Dynamic_Object_Master::getAncestors($args);
}
?>