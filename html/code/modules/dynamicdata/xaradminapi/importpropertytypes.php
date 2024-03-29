<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Check the properties directory for properties and import them into the Property Type table.
 *
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        boolean  $args[flush] flush the property type table before import true/false (optional)<br/>
 *        array    $args[dirs]
 * @return array<mixed> an array of the property types currently available
 */
function dynamicdata_adminapi_importpropertytypes(array $args = [], $context = null)
{
    sys::import('modules.dynamicdata.class.properties.registration');
    extract($args);
    if(!isset($flush)) {
        $flush = true;
    }
    if(!isset($dirs)) {
        $dirs = [];
    }
    try {
        $proptypes = PropertyRegistration::importPropertyTypes($flush, $dirs);
    } catch (Exception $e) {
        throw $e;
    }
    return $proptypes;
}
