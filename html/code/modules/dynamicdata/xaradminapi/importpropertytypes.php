<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Check the properties directory for properties and import them into the Property Type table.
 *
 * @param array    $args array of optional parameters<br/>
 *        boolean  $args[flush] flush the property type table before import true/false (optional)<br/>
 *        array    $args[dirs]
 * @return array an array of the property types currently available
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_importpropertytypes(Array $args=array())
{
    sys::import('modules.dynamicdata.class.properties.registration');
    extract($args);
    if(!isset($flush)) {
       $flush = true;
    }
    if(!isset($dirs)) {
       $dirs = array();
    }
    try {
        $proptypes = PropertyRegistration::importPropertyTypes($flush,$dirs);
    } catch (Exception $e) {
        throw $e;
    }
    return $proptypes;
}
?>
