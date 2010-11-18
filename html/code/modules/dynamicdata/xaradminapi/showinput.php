<?php
/**
 * Show predefined form input field
 *
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * show some predefined form input field in a template
 *
 * @param array    $args array of optional parameters<br/>
 * @param $args array containing the definition of the field (type, name, value, ...)
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_adminapi_showinput(Array $args=array())
{
    $property = & DataPropertyMaster::getProperty($args);

    if (!empty($args['preset']) && empty($args['value'])) {
        return $property->_showPreset($args);

    } elseif (!empty($args['hidden'])) {
        return $property->showHidden($args);

    } else {
        return $property->showInput($args);
    }
    // TODO: input for some common hook/utility modules
}
?>