<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Check the properties directory for properties and import them into the Property Type table.
 *
 * @param $args[flush] flush the property type table before import true/false (optional)
 * @param array $args[dirs]
 * @return array an array of the property types currently available
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_importpropertytypes($args)
{
    sys::import('modules.dynamicdata.class.properties.registration');
    extract($args);
    if(!isset($flush)) {
       $flush = true;
    }
    if(!isset($dirs)) {
       $dirs = array();
    }
    $proptypes = PropertyRegistration::importPropertyTypes($flush,$dirs);
    return $proptypes;
}
?>
