<?php
/**
 * Get the list of defined property types
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * get the list of defined property types from somewhere...
 *
 * @author the DynamicData module development team
 * @returns array
 * @return array of property types
 * @throws DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getproptypes($args)
{
    return Dynamic_Property_Master::getPropertyTypes();
}

?>
