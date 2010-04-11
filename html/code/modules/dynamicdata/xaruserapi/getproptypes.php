<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Get the list of defined property types
 *
 * @author the DynamicData module development team
 * @return array of property types
 * @throws DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getproptypes($args)
{
    return DataPropertyMaster::getPropertyTypes();
}

?>
