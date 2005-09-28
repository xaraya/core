<?php
/**
 * File: $Id$
 *
 * Show some predefined form input field in a template
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/
/**
 * show some predefined form input field in a template
 *
 * @param $args array containing the definition of the field (type, name, value, ...)
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_adminapi_showinput($args)
{
    $property = & Dynamic_Property_Master::getProperty($args);

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
